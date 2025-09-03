# Andrew-Gos/Serializer

[![Latest Stable Version](https://poser.pugx.org/andrew-gos/serializer/v/stable)](https://packagist.org/packages/andrew-gos/serializer)
[![License](https://poser.pugx.org/andrew-gos/serializer/license)](https://packagist.org/packages/andrew-gos/serializer)
<!-- TODO: Add more badges for build status, code coverage, etc. -->

A flexible and extensible serialization library for modern PHP applications.

This library provides a simple yet powerful way to convert complex PHP data structures, including objects and arrays, into various string formats like JSON and XML.

## 🚀 Key Features

*   **Flexible Architecture:** Easily add your own custom normalizers for your objects and encoders for your desired output formats.
*   **Built-in Encoders:** Comes with ready-to-use encoders for JSON and XML.
*   **Advanced XML Handling:** Automatically handles data duplication and circular references in arrays, producing a clean, referenced XML structure.
*   **Modern PHP:** Built for PHP 8.2+ with strict typing.
*   **No Side Effects:** The encoders are designed to be "pure" and will not modify your original input data.

## 🛠️ Installation

The project requires PHP 8.2 or higher.

Install the library via [Composer](https://getcomposer.org/):

```bash
composer require andrew-gos/serializer
```

## 🏁 Quick Start
The `Serializer` is highly configurable and requires the registration of **normalizers** (to convert data into a serializable array/scalar) and **encoders** (to convert that array into a string).

```php
<?php

require 'vendor/autoload.php';

use AndrewGos\Serializer\Encoder\JsonEncoder;
use AndrewGos\Serializer\Encoder\XmlEncoder;
use AndrewGos\Serializer\Serializer;

// 1. Create a new Serializer instance.
$serializer = new Serializer();

// 2. Register normalizers. For basic data that is already serializable,
// a simple "pass-through" normalizer is sufficient.
$serializer->addNormalizer('array', fn(array $data) => $data);

// 3. Register encoders for the desired output formats.
$serializer->addEncoder('json', new JsonEncoder());
$serializer->addEncoder('xml', new XmlEncoder());


// 4. Prepare your data.
$data = [
    'user' => 'John Doe',
    'posts' => [
        ['id' => 1, 'title' => 'First Post'],
        ['id' => 2, 'title' => 'Second Post'],
    ],
];

// 5. Serialize!
// To JSON
$json = $serializer->serialize($data, 'json');
echo "JSON Output:\n";
echo $json . "\n\n";

// To XML
$xml = $serializer->serialize($data, 'xml');
echo "XML Output:\n";
echo $xml . "\n";
```

## 🔗 The XML Reference System (Deep Dive)
A unique feature of the `XmlEncoder` is its ability to handle complex array structures, including data duplication and circular references.
### How It Works
When the `XmlEncoder` encounters an array, it checks if it has seen the exact same array instance before.
- **First Encounter:** The array is fully serialized and placed within a `<references>` (references) block. It is assigned a unique key.
- **Subsequent Encounters:** Instead of serializing the entire array again, a simple reference tag, `<reference key="..."/>`, is inserted, pointing to the array's key in the `<references>` block.

### Important Nuance: The "Lost" Top-Level Reference
There is a key behavior to understand related to how PHP handles function parameters. The encoder's `__invoke` method accepts the array **by value**, not by reference.
- **What this means:** When you call `($encoder)($data)`, PHP creates a shallow **copy** of the top-level array. This breaks the reference link for the outermost array.
- **The Effect:** The encoder sees two different array instances: the outer copy, and the inner array which still correctly references itself. This results in an "extra" entry in the `<references>` block.

### Example: A Circular Reference
```php
$data = ['name' => 'My Array'];
$data['reference_to_self'] = &$data; // Create a self-reference

$xml = (new XmlEncoder())($data);
echo $xml;
```
The output will look similar to this (keys will vary), but **notice the two entries in the block`<references>`**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <references>
    <!-- Entry #1: The inner, truly recursive array -->
    <reference key="key_A">
      <array>
        <item key="name"><string>My Array</string></item>
        <item key="reference_to_self"><reference key="key_A"/></item>
      </array>
    </reference>
    <!-- Entry #2: The outer copy, created by the pass-by-value call -->
    <reference key="key_B">
      <array>
        <item key="name"><string>My Array</string></item>
        <item key="reference_to_self"><reference key="key_A"/></item>
      </array>
    </reference>
  </references>
  <data>
    <!-- The main data points to the outer copy -->
    <reference key="key_B"/>
  </data>
</root>
```
This is the expected and correct behavior, as confirmed by the project's test suite.

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
