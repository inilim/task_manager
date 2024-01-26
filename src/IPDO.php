<?php

namespace Inilim\TaskManager;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Обертка над PDO
 */
class IPDO
{
   static string $host = 'localhost';
   static string $name = '';
   static string $login = '';
   static string $pass = '';
   /**
    * Соединение с БД PDO
    */
   protected ?PDO $link = null;
   protected int $lenQuery = 1000;
   /**
    * статус последнего запроса.
    */
   public bool $lastStatus = false;
   /**
    * количество соединений
    */
   public int $countConnect = 0;
   /**
    * количество измененных строк поледнего запроса.
    */
   public int $countChanges = 0;
   /**
    * id автоинкремента последнего запроса INSERT
    */
   public int $lastInsertId = -1;
   /**
    * код ошибки последнего запроса. Код выдает обьект statement
    */
   public $lastError = '';

   const FETCH_ALL = 2;
   const FETCH_ONCE = 1;

   static bool $getNonAssocArray = false;
   static ?self $obj = null;
   static int $count = 0;

   function __construct()
   {
      static::$obj = $this;
      $this->connectDB();
   }

   /**
    * активируем транзакцию, если мы повторно активируем транзакцию будет вызван rollBack
    */
   public function begin(): bool
   {
      if ($this->link === null) return false;
      if ($this->link->inTransaction()) {
         $this->link->rollBack();
         return false;
      }
      $this->link->beginTransaction();
      return true;
   }

   public function rollBack(): void
   {
      if ($this->link === null) return;
      if ($this->link->inTransaction()) $this->link->rollBack();
   }

   public function inTransaction(): bool
   {
      if ($this->link === null) return false;
      return $this->link->inTransaction();
   }

   public function commit(): bool
   {
      if ($this->link === null) return false;
      if ($this->link->inTransaction()) {
         $this->link->commit();
         return true;
      }
      return false;
   }

   protected function defineConst(): int
   {
      if (static::$getNonAssocArray) {
         static::$getNonAssocArray = false;
         return PDO::FETCH_NUM;
      } else return PDO::FETCH_ASSOC;
   }

   public function closeConnect(): void
   {
      $this->link = null;
   }

   public function run(
      string $sql,
      array $values = [],
      int $fetch = 0
   ): array {
      $this->countChanges = 0;
      $this->lastInsertId = -1;
      $result = $this->tryMainProccess($sql, $values);
      return $this->defineFetch($result, $fetch);
   }

   protected function defineFetch(array &$result, int $fetch): array
   {
      if (!isset($result['statement'])) return [];

      if ($fetch === static::FETCH_ONCE) {
         $list = $result['statement']->fetch(PDO::FETCH_ASSOC);
         return $list === false ? [] : $list;
      }
      if ($fetch === static::FETCH_ALL) {
         $list = $result['statement']->fetchAll($this->defineConst());
         return $list === false ? [] : $list;
      }

      return $result;
   }

   protected function tryMainProccess(string &$sql, array &$values = []): array
   {
      try {
         $this->lastError = '';
         $this->lastStatus = true;
         return $this->mainProccess($sql, $values);
      } catch (Throwable $e) {
         $this->lastStatus = false;
         // writeLog(self::class, [
         //    'exception_data' => CollectDataException($e),
         //    'query' => $this->shortQuery($sql),
         //    'values' => $values ?? [],
         // ]);
         unset($values);
         return [];
      }
   }

   protected function mainProccess(string &$sql, array &$values = []): array
   {
      // соединение
      $this->connectDB();

      if ($this->link === null) throw new PDOException('Нету соединения. is_null');
      if (!\is_object($this->link)) throw new PDOException('new PDO вернул не обьект');

      // IN OR NOT IN (:item,:item,:item)
      $sql = $this->convertList($values, $sql);

      // тут удаляем ненужные ключи данные
      // $masks = $this->removeUnwantedKeys($values, $sql);
      $this->removeUnwantedKeys($values, $sql);
      // добавляем недостающиеся маски
      // $this->addMask($masks, $values, $sql);
      // unset($masks);

      // подготовка запроса
      $stm = $this->link->prepare($sql);

      if (\is_bool($stm)) throw new PDOException('$stm === false. prepare вернул false. ' . \json_encode($this->link->errorInfo()));

      // Устанавливаем параметры к запросу
      $this->setBindParams($stm, $values);

      // выполнить запрос
      if (!$stm->execute()) {
         $error_info = $stm->errorInfo();
         $this->lastError = $error_info[0] ?? '';
         throw new PDOException('$stm->execute === false. ' . \json_encode($error_info));
      }
      static::$count++;
      unset($values);
      return $this->defineResult($stm);
   }

   /**
    * Из-за PDO::ATTR_EMULATE_PREPARES не работают одинаковые маски в запросах
    *
    * @param string[] $masks
    */
   // protected function addMask(array $masks, array &$values, string &$sql): void
   // {
   //    if (!sizeof($masks)) return;
   //    $masks = array_count_values($masks);
   //    $masks = array_filter($masks, fn ($v) => $v > 1);
   //    if (!sizeof($masks)) return;
   //    $hashes = [];
   //    foreach ($masks as $mask_name => $count_repet) {
   //       $repet_value = $values[$mask_name];
   //       $count_repet--;
   //       for ($i = 0; $i < $count_repet; $i++) {
   //          $new_mask = $mask_name . $i;
   //          $hashes[$new_mask] = md5($new_mask);
   //          $sql = $this->replaceOnce(':' . $mask_name, $hashes[$new_mask], $sql);
   //          $values[$new_mask] = $repet_value;
   //       }
   //    }
   //    $masks = array_keys($hashes);
   //    $masks = array_map(fn ($m) => ':' . $m, $masks);
   //    $sql = str_replace($hashes, $masks, $sql);
   // }

   /**
    * TODO нужно потестить
    * @return string[]
    */
   protected function removeUnwantedKeys(array &$values, string $sql): array
   {
      if (!\str_contains($sql, ':')) return [];
      $masks = [];
      \preg_match_all('#\:[a-z\_A-Z0-9]+#', $sql, $masks);
      $masks = $masks[0] ?? [];
      if (!\sizeof($masks)) return $masks;
      $masks = \array_map(fn ($m) => \trim($m, ':'), $masks);
      $masks_keys = \array_flip($masks);
      $values = \array_intersect_key($values, $masks_keys);
      return $masks;
   }

   private function replaceOnce(string $search, string $replace, string $txt): string
   {
      $pos = \strpos($txt, $search);
      return $pos !== false ? \substr_replace($txt, $replace, $pos, \strlen($search)) : $txt;
   }

   /**
    * В момент создания PDO может выбросить исключение PDOException
    *
    * @throws PDOException
    */
   protected function connectDB(): void
   {
      if ($this->link === null) {
         $this->countConnect++;
         $this->link = new PDO(
            'mysql:dbname=' .
               static::$name .
               ';host=' .
               static::$host,
            static::$login,
            static::$pass,
            [
               // \PDO::MYSQL_ATTR_FOUND_ROWS => true,
               // TODO Выдает значения соответствующие типам столбцов, НО выдает ошибку при повторных дырках в запросе!!!!
               // PDO::ATTR_EMULATE_PREPARES => false,
               // преобразует числовые значения в строки
               // PDO::ATTR_STRINGIFY_FETCHES => false,
            ]
         );
         $this->link->exec('SET NAMES utf8mb4');
      }
   }

   /**
    * column in (:ids) > column in (:in_item_1,:in_item_2,:in_item_3)
    */
   protected function convertList(array &$values, string &$sql): string
   {
      $mark = 'in_item_';
      $num = \mt_rand(1000, 9999);
      foreach ($values as $key_val => $val) {

         if (\is_array($val)) {
            $mark_keys = \array_map(function ($val_item) use (&$values, $mark, &$num) {
               $new_key = $mark . $num;
               $values[$new_key] = $val_item;
               $num++;
               return ':' . $new_key;
            }, $val);

            $sql = \str_replace(':' . $key_val, \implode(',', $mark_keys), $sql);
            unset($values[$key_val]);
         }
      }

      return $sql;
   }

   // protected function mysql_escape_string(string $unescaped_string): string
   // {
   //    $replacementMap = [
   //       "\0" => "\\0",
   //       "\n" => "\\n",
   //       "\r" => "\\r",
   //       "\t" => "\\t",
   //       chr(26) => "\\Z",
   //       chr(8) => "\\b",
   //       '"' => '\"',
   //       "'" => "\'",
   //       '_' => "\_",
   //       "%" => "\%",
   //       '\\' => '\\\\'
   //    ];

   //    return \strtr($unescaped_string, $replacementMap);
   // }

   protected function setBindParams(PDOStatement &$stm, array &$values): void
   {
      // &$val требование от bindParam https://www.php.net/manual/ru/pdostatement.bindparam.php#98145
      foreach ($values as $key => &$val) {
         $mask = ':' . $key;
         if (integer()->isIntPHP($val)) {
            $val = \intval($val);
            $stm->bindParam($mask, $val, PDO::PARAM_INT);
         } elseif ($val === null) {
            $stm->bindParam($mask, $val, PDO::PARAM_NULL);
         } else {
            $val = \strval($val);
            $stm->bindParam($mask, $val, PDO::PARAM_STR);
         }
      }
   }

   protected function defineResult(PDOStatement &$stm): array
   {
      return [
         'statement' => $stm,
         'countChanges' => $this->countChanges = $stm->rowCount(),
         'lastInsertId' => $this->lastInsertId = $this->getLastInsertId(),
      ];
   }

   protected function getLastInsertId(): int
   {
      $res = $this->link->lastInsertId();
      // lastInsertId может вернуть строку, представляющую последнее значение
      if (integer()->isNumeric($res)) return \intval($res);
      return -1;
   }

   /**
    * форматируем запрос для логов
    */
   protected function shortQuery(string &$sql): string
   {
      $sql = \str_replace(["\n", "\r", "\r\n", "\t"], ' ', $sql);
      $sql = \preg_replace('#\ {2,}#', ' ', $sql);
      if (\strlen($sql) > $this->lenQuery) return \substr($sql, 0, $this->lenQuery) . '...';
      return $sql;
   }

   /**
    * выполнить запрос
    * @param int $s 0 не вытаскивать результат, 1 вытащить один результат, 2 вытащить все.
    */
   static function exec(
      string $sql_query,
      int|array $array = [],
      int $f = 0
   ): array {
      static::init();
      if (\is_int($array)) {
         $f = $array;
         $array = [];
      }
      return static::$obj->run($sql_query, $array, $f);
   }

   static function init(): void
   {
      if (static::$obj === null) new static();
   }

   static function isInit(): bool
   {
      return static::$obj !== null;
   }

   /**
    * получить статус последнего запроса.
    */
   static function status(): bool
   {
      return static::$obj->lastStatus;
   }

   /**
    * количество строк, которой затронул последний запрос
    */
   static function countChanges(): int
   {
      return static::$obj->countChanges ?? 0;
   }

   /**
    * получить id последнего запроса INSERT, при неудаче вернет -1
    */
   static function getLastInsert(): int
   {
      return static::$obj->lastInsertId ?? -1;
   }

   /**
    * получить код ошибки последнего запроса.
    */
   static function getLastError(): string
   {
      return static::$obj->lastError ?? '';
   }

   /**
    * закрыть соединение с базой
    */
   static function close(): void
   {
      if (\is_object(static::$obj)) static::$obj->closeConnect();
   }
}
