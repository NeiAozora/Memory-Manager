# MemoryManager

**A Lightweight PHP Library for Byte Manipulation and Memory Management**

## Overview

The **MemoryManager** library provides an efficient and intuitive way to manipulate byte data and manage memory streams in PHP. It is designed for handling data-heavy use cases, such as working with binary files like `.png`, `.wad`, or other formats. The library supports two memory management systems:

- **Hard Reference System**: Traditional PHP object references with manual memory management.
- **Weak Reference System**: Uses PHP's `WeakReference` to allow automatic garbage collection when objects are no longer referenced.

The library is built around a base class (`Memory`) that abstracts core functionality. Two implementations are provided out of the box:
- **`MemoryStream`**: Allocates memory in RAM for byte manipulation.
- **`TempMemory`**: Starts with memory storage but switches to temporary files for large data.

## Features

- **Byte Manipulation**: Easily write and read byte arrays or binary data.
- **Dynamic Storage**: Use in-memory storage for small data or transition to temporary files for larger datasets.
- **Dual Memory Management**:
  - **Hard Reference System**: Traditional PHP references with explicit memory management.
  - **Weak Reference System**: Automatic garbage collection using `WeakReference`.
- **Extensible**: Extend the `Memory` base class to create custom storage solutions.
- **Simple API**: Perform operations with minimal code:
  ```php
  $streamMem->writeBytes($arrayOfBytes);
  $data = $streamMem->readBytes();
  ```

## Installation

### Via Composer

Install the library using Composer:

```bash
composer require neiaozora/memory-manager
```

### Manual Installation

Clone the repository or download it directly from GitHub:

```bash
git clone https://github.com/NeiAozora/MemoryManager.git
```

Include the library in your project:

```php
require_once 'vendor/autoload.php';
```

## Usage

### Example: Demonstrating Hard Reference and Weak Reference Systems

The following example demonstrates the hard reference and weak reference systems using the `MemoryStream` class. It also includes a `printMemoryUsage()` function to debug memory usage.

```php
use NeiAozora\MemoryManager\MemoryStream;

require_once "vendor/autoload.php";

// Function to debug memory usage
function printMemoryUsage() {
    $bytes = memory_get_usage();
    $kilobytes = $bytes / 1024;
    $megabytes = $kilobytes / 1024;

    printf(
        "Memory Usage: %d Bytes | %.2f KB | %.2f MB\n",
        $bytes,
        $kilobytes,
        $megabytes
    );
}

// File size is 512KB
$file = "random.bin";

// Print initial memory usage
printMemoryUsage();

// 1. Hard Reference System
$mem = new MemoryStream(); // Creates a hard-referenced object
$mem->write(file_get_contents($file)); // Writes 512KB of data to memory

// Print memory usage after writing data
printMemoryUsage();

// Free memory by unsetting the hard reference
unset($mem);

// Print memory usage after freeing memory
printMemoryUsage();

// 2. Weak Reference System
$weakRefMemory = MemoryStream::new(); // Creates a weak-referenced object
$weakRefMemory->get()->write(file_get_contents($file)); // Writes 512KB of data to memory

// Print memory usage after writing data
printMemoryUsage();

// Free memory by destroying the weak reference
$weakRefMemory->get()->destroy();

// Print memory usage after freeing memory
printMemoryUsage();
```

## Another usage examples

```php
require_once 'vendor/autoload.php';

use NeiAozora\MemoryManager\MemoryStream;
use NeiAozora\MemoryManager\TempMemory;

// Usage
// 1. Writing and Reading Bytes
// Example: Using MemoryStream

use NeiAozora\MemoryManager\MemoryStream;

$memory = new MemoryStream(); // uses Hard reference mode

// Write an array of bytes (8-bit values between 0 and 255) to memory
$bytes = [1, 2, 3, 4, 255];
$memory->writeBytes($bytes);

// Write binary data as a string to memory (don't ask me why it's a string, ask PHP due to its quirks)
// $data = 'Hello, World!';
// or 
// $data = file_get_contents("example.png");
// $memory->write($data);


// Read back the data
$data = $memory->readBytes();
print_r($data); // Outputs: [1, 2, 3, 4, 255]

// Read data from the stream
$data = $memory->read();
echo $data;

// The memory will be released once it's no longer needed
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

## Using with Custom File Formats
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

## Memory Management Systems Explained

### 1. Hard Reference System
**How It Works**: Objects are created using traditional PHP references. Memory is managed manually by calling `unset()`.

**Behavior**:
- When `$mem` is created, it holds a strong reference to the `MemoryStream` object.
- Calling `unset($mem)` explicitly frees the memory, reducing memory usage.

**Use Case**: Ideal for scenarios where you need full control over memory management.

### 2. Weak Reference System
**How It Works**: Objects are wrapped in PHP's `WeakReference`, allowing them to be garbage-collected when no longer referenced. You can still explicitly free memory using `destroy()`.

**Behavior**:
- When `$weakRefMemory` is created, it holds a weak reference to the `MemoryStream` object.
- Calling `$weakRefMemory->get()->destroy()` explicitly frees the memory, reducing memory usage.

**Use Case**: Ideal for scenarios where you want to avoid memory leaks and rely on automatic garbage collection.

## Detailed Explanation of Weak Reference Usage Rules

### Important Weak Reference Usage Rules

When using the weak reference system with `::new()`, observe the following rules to ensure proper memory management:

### 1. Do Not Assign the Weakly Referenced Object to a Strong Reference
When calling `$weakRefMemory->get()`, it returns a strong reference to the object. If you assign this strong reference to another variable, it increments the reference count, preventing the object from being freed as expected by the garbage collector.

**Example of Incorrect Usage**:

```php
$weakRefMemory = MemoryStream::new(); // The object of Memory class now has a reference count of 1 (held inside the Class $memory attribute).
$mem = $weakRefMemory->get();  // This creates a strong reference to the object, increasing the reference count to 2.
$mem->destroy();                // This only frees the reference from within the Class $memory attribute, reducing the reference count to 1.
unset($mem);                    // Now the object may be freed because $mem was the strong reference holding the last reference to the object.
```

**Why This Breaks the Intended Lifecycle**:
- The object remains alive as long as `$mem` holds the strong reference, preventing it from being freed until `unset($mem)` is called.

### 2. Only Use `WeakReference::get()` Directly
Manipulate the object directly using the weak reference wrapper without creating a strong reference. This ensures the object can be freed as soon as it is no longer in use.

**Example of Correct Usage**:

```php
$weakRefMemory = MemoryStream::new();
$weakRefMemory->get()->write(file_get_contents($file)); // Write data
$weakRefMemory->get()[0] = 0;  // Assign byte value 0 to offset 0
$weakRefMemory->get()->destroy();  // Frees memory when done
```

**Why This Works**:
- The object is only weakly referenced, and it will be freed when no other strong references remain.

### Key Rule:
Do not assign the object referenced by a weak reference to any new variable or hold a strong reference to it after calling `get()`. This ensures the object can be freed when it's no longer needed.

## Why Weak References?

Weak references are essential for efficient memory management in low-level operations. The library uses PHP's `WeakReference` to avoid increasing reference counts, allowing objects to be garbage-collected when no longer referenced. However, you can explicitly free memory by calling `unset()` or `destroy()`.

### Key Points:
- Objects are eligible for garbage collection when no other references exist.
- Explicitly call `unset($object)` or `destroy()` to free memory immediately.
- Weak references prevent memory leaks in long-running applications.

## Supported PHP Versions

- Only PHP 8+

## Contributing

Contributions are welcome! Feel free to:
- Submit pull requests.
- Report issues or suggest improvements in the GitHub repository.

## License

This project is licensed under the MIT License. See the LICENSE file for details.
