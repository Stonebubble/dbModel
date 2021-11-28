# DBModel

A fast, lightweight and simple model builder for your relational database. It reduces writing the same code over and over without compromising on speed by generating each model for your MVC architecture.

## Requirements

* PHP 8.1 (it uses Enums)
    * modules: PDO 
* mariadb/mysql database

## Installtion/Setup
Generate the model every time you update your db structure:
```
composer require jbschmitt/dbmodel
composer exec dbmodel generate-all
```

It will ask you for a config location and relative to that a folder to include all "./Table/TablenameTable.php" and "./Base/TableNameBase.php".


## Documentation
Every Table class is static and implements:
```
/**
 * array of primary key column(s)
 *
 * @return array
*/
public static function primaryKey():array

/**
 * columns of this table as an associative array
 *
 * @return array
 */
public static function columns():array


```

Also each Base model class provides:
* getter and setter for each column
* update, insert, save, isNotNullValues and ::where methods

## Example
A Table "reservation" with these columns exists:
| code | active | timestamp |
| --- | --- | --- |
| a1Hq | 0 | 2021-11-24 03:59:30 |
| 9eWc | 1 | 2021-11-21 00:49:59 |
| ...  | ... | ... |
<br>
```
$reservation = Reservation::where(['code' => "a1Hq"])->toModels()[0];
// where returns a Collection which can be converted to an array or the Model
$reservation->setActive(1);
$reservation->save(); 
// performs update, because it recognizes, that it already exists

```
