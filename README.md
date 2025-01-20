# MemoryManager

**A Lightweight PHP Library for Byte Manipulation and Memory Management**

## Overview

The **MemoryManager** library provides an easy and efficient way to manipulate byte data and manage memory streams in PHP. With this library, you can assign, manipulate, and read bytes from memory, or switch to temporary file storage when needed. Perfect for handling data-heavy use cases like working with `.png`, `.wad`, or other binary file types, and can be able to use everywhere without adding reference counting, and will be really destroyed when unset().

The library uses a **base class (`Memory`)** for abstracting PHP's `fopen` functionality, offering two implementations out of the box:  
- **`MemoryStream`**: Allocates memory in RAM for byte manipulation.  
- **`TempMemory`**: Starts with memory storage but transitions to temporary files for large data.  

## Features

- **Easy Byte Manipulation**: Write and read byte arrays seamlessly.
- **Memory and Temp Storage**: Dynamically allocate memory or use temporary file storage as needed.
- **Weak Reference Support**: Class objects are weak-referenced by default to prevent reference counting issues and allow garbage collection when not in use.
- **Extensibility**: Extend the `Memory` base class to create custom implementations for specific storage requirements.
- **Simple API**: Write and read bytes with minimal code:
```php
$streamMem->writeBytes($arrayOfBytes);
$data = $streamMem->readBytes();
```

Installation
Clone the repository or download it directly from GitHub.

```bash
git clone https://github.com/NeiAozora/MemoryManager.git
Include the library in your project:
```

or install it through composer

```bash
composer require neiaozora/memory-manager
```


```php
require_once 'vendor/autoload.php';

use NeiAozora\MemoryManager\MemoryStream;
use NeiAozora\MemoryManager\TempMemory;

// Usage
// 1. Writing and Reading Bytes
// Example: Using MemoryStream

use NeiAozora\MemoryManager\MemoryStream;

$memory = new MemoryStream(); // the base class Memory is weak referenced by default, so it will really be destroyed when unset()

// Write an array of bytes (0-255) to the memory
$bytes = [1, 2, 3, 4, 255];
$memory->writeBytes($bytes);

// write an binary data in string to the memory, don't ask me why string, ask php due to its quirks
// $data = 'Hello, World!';
// or 
// $data = file_get_contents("example.png");
// $memory->write($data);


// Read back the data
$data = $memory->readBytes();
print_r($data); // Outputs: [1, 2, 3, 4, 255]

// Read data from the stream
$data = $tempMemory->read();
echo $data;

// Will Release be released, memory when it's done and no longer needed
unset($memory);



// Example: Using TempMemory

use NeiAozora\MemoryManager\TempMemory;

$tempMemory = new TempMemory();

// Write binary data to the temp stream
$tempMemory->writeBytes([10, 20, 30]);

// Read data from the stream
$data = $tempMemory->read();
echo $data;

// Release memory
unset($tempMemory);
```

## 2. Using with Custom File Formats
Easily handle file data like .png or .wad by reading their raw bytes into memory, manipulating the byte data, and writing it back.

```php
$file = fopen('example.png', 'rb');
$memory = new MemoryStream();

while (!feof($file)) {
    $chunk = fread($file, 1024);
    $memory->write($chunk);
}

fclose($file);

// Perform operations on the byte data in $memory
$data = $memory->read();

// Save it back to a new file
$newFile = fopen('modified.png', 'wb');
fwrite($newFile, $data);
fclose($newFile);

// Cleanup
unset($memory);
```

## Extensibility
To create your own custom memory management solution, extend the Memory base class and define the openStream method.

```php
use NeiAozora\MemoryManager\Memory;
use NeiAozora\MemoryManager\IOException;

class CustomMemory extends Memory
{
    protected function openStream()
    {
        $this->stream = fopen('php://custom', 'wb+');
        if (!$this->stream) {
            throw new IOException("Failed to open custom stream.");
        }
    }
}
```
## Why Weak References?
When dealing with low-level memory management, efficiency is key. The library uses PHP's WeakReference to avoid increasing reference counts when memory streams are passed around in the application. This allows the objects to be eligible for garbage collection when they are no longer referenced, but you must explicitly call unset() to trigger the cleanup process.

This means:

The object won't increase its reference count, and PHP can decide to clean it up automatically once there are no other references left.
While weak references help manage memory efficiently by allowing PHP’s garbage collector to clean up unreferenced objects, it’s still a good practice to call unset($object) to explicitly free the memory at the right time.
For example:

```php
// The Memory class is weak-referenced by default, so the object is eligible for garbage collection.
$memory = new MemoryStream();

// The object is freed when `unset($memory)` is called.
unset($memory); // This ensures the object is destroyed immediately.
```

# Supported PHP Versions
PHP 7.4+
Compatible with PHP 8.x

# Contributing
Feel free to contribute to the project! Submit pull requests or report issues in the GitHub repository.
