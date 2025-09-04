# Andrew-Gos/Serializer

[![Latest Stable Version](http://poser.pugx.org/andrew-gos/serializer/v)](https://packagist.org/packages/andrew-gos/serializer)
[![Total Downloads](http://poser.pugx.org/andrew-gos/serializer/downloads)](https://packagist.org/packages/andrew-gos/serializer)
[![Latest Unstable Version](http://poser.pugx.org/andrew-gos/serializer/v/unstable)](https://packagist.org/packages/andrew-gos/serializer)
[![License](http://poser.pugx.org/andrew-gos/serializer/license)](https://packagist.org/packages/andrew-gos/serializer)
[![PHP Version Require](http://poser.pugx.org/andrew-gos/serializer/require/php)](https://packagist.org/packages/andrew-gos/serializer)

A flexible and extensible serialization library for modern PHP applications.

This library provides a simple yet powerful way to convert complex PHP data structures, including objects and arrays, into various string formats like JSON and XML.

## 🚀 Key Features
* **Flexible Architecture:** Easily add your own custom normalizers and encoders.
* **Object & Array Support:** Correctly handles scalars, arrays, and objects.
*   **Built-in Encoders:** Comes with ready-to-use encoders for JSON and XML.
* **Advanced XML Handling:** Automatically manages data duplication and circular references for both arrays and objects.
*   **Modern PHP:** Built for PHP 8.2+ with strict typing.
*   **No Side Effects:** The encoders are designed to be "pure" and will not modify your original input data.

## 🛠️ Installation
The project requires PHP 8.2 or higher.

Install the library via [Composer](https://getcomposer.org/):

```bash
composer require andrew-gos/serializer
```

## 🏁 Quick Start
The `Serializer` is highly configurable and requires the registration of **normalizers** (to convert data into a serializable format) and **encoders** (to convert
that format into a string).

### Example 1: Serializing an Array
```php
<?php
require 'vendor/autoload.php';

use AndrewGos\Serializer\Encoder\JsonEncoder;
use AndrewGos\Serializer\SerializerFactory;

// Use factory to set default normalizers (for scalars, arrays, etc.) and encoders (for json and xml)
$serializer = SerializerFactory::getDefaultSerializer();
// For array serializer will use default normalizer
$serializer->addEncoder('json', new JsonEncoder());

$data = ['user' => 'John Doe'];
$json = $serializer->serialize($data, 'json'); // Output: {"user":"John Doe"}
echo $json;
```

### Example 2: Serializing an Object
```php
<?php
// ... (use statements)

$serializer = SerializerFactory::getDefaultSerializer();

// Register a custom normalizer for our object.
// It should return an array, a scalar, or another object.
$serializer->addNormalizer(
    stdClass::class,
    function(stdClass $obj) {
        return (array) $obj; // Simply cast the object to an array
    },
);

$data = (object)['user' => 'Jane Doe'];
$json = $serializer->serialize($data, 'json'); // Output: {"user":"Jane Doe"}
echo $json;
```

## 🔗 Advanced Serialization: The XML Reference System
A unique feature of the `XmlEncoder` is its ability to handle complex data structures, including duplication and circular references. This prevents infinite loops
and reduces output size.

### How It Works
When the `XmlEncoder` encounters an array or an object, it checks if it has seen the exact same instance before.

- **First Encounter:** The data is fully serialized and placed within a `<references>` block. It is assigned a unique key.
- **Subsequent Encounters:** Instead of serializing the data again, a simple `<reference key="..."/>` tag is inserted.

### Reference Handling Nuances: Arrays vs. Objects
The reference system behaves differently for arrays and objects due to how PHP passes them to functions.

#### Arrays and the "Pass-by-Value" Nuance
In PHP, arrays are passed to functions **by value** (a shallow copy is created). This causes the `XmlEncoder` to see an "extra" top-level reference.
**Example:**

```php
$data = ['name' => 'Recursive Array'];
$data['reference_to_self'] = &$data; // A self-reference

$xml = (new XmlEncoder())($data);
```

**Result (Note the 2 entries in `<references>`):**

```xml
<root>
  <references>
    <!-- #1: The inner, truly recursive array -->
    <reference key="key_A">
      <array>
        <item key="name"><string>Recursive Array</string></item>
        <item key="reference_to_self"><reference key="key_A"/></item>
      </array>
    </reference>
    <!-- #2: The outer copy created by the pass-by-value call -->
    <reference key="key_B">
      <array>
        <item key="name"><string>Recursive Array</string></item>
        <item key="reference_to_self"><reference key="key_A"/></item>
      </array>
    </reference>
  </references>
  <data><reference key="key_B"/></data>
</root>
```

#### Objects and "Pass-by-Reference" Behavior
In PHP, objects are always passed **by reference**. This means the encoder works with the original instance, and the "extra" reference problem **does
not occur**.
**Example:**

```php
$data = new stdClass();
$data->name = 'Recursive Object';
$data->reference_to_self = &$data; // A self-reference

$xml = (new XmlEncoder())($data);
```

**Result (Note only 1 entry in `<references>`):**

```xml
<root>
  <references>
    <reference key="key_A">
      <object>
        <property name="name"><string>Recursive Object</string></property>
        <property name="reference_to_self"><reference key="key_A"/></property>
      </object>
    </reference>
  </references>
  <data><reference key="key_A"/></data>
</root>
```

This is the expected and correct behavior, allowing the `XmlEncoder` to reliably handle both data types.

## 🧪 Testing
To run the test suite, first ensure you have installed the development dependencies:

```bash
composer install --dev
```
Then, run PHPUnit:
```bash
./vendor/bin/phpunit tests
```

## 🤝 Contributing
Contributions are welcome! Please feel free to submit a pull request or open an issue.

## 📜 License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
