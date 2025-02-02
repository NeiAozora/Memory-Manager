<?php

namespace NeiAozora\MemoryManager;

use WeakReference;

/**
 * Abstract Class Memory
 * 
 * A base class for managing memory streams. It abstracts the logic for opening, managing, and closing streams.
 * The derived classes will specify the type of stream (memory or temp) to be used.
 * 
 * This class manages the creation of memory buffer for memory management.
 * This class has accommodate 2 mode, for regular hard reference and for weak reference.
 * 
 * **Important Weak Reference Usage Rules:**
 * 
 * If you use the weak reference for this class by using `::new()`, observe the following:
 * 
 * - **Do not assign the weakly referenced object to a strong reference.**
 *   When calling `$weakRefMemory->get()`, it **returns a value strong reference** of an object. 
 *   When you assing it to another var this will **increments the reference count**, which means the object will **not be freed** as expected by the garbage collector until this strong reference is unset.
 *   
 *   **Example of incorrect usage:**
 *   ```php
 *   $weakRefMemory = MemoryStream::new(); // The object of Memory class now has counted 1 (which was held inside the Class $memory atribute)
 *   $mem = $weakRefMemory->get();  // This creates a strong reference to the object, which now the object has counted 2 reference, which prevents it from being freed from memory.
 *   $mem->destroy();                // This does not affect the memory object (A.K.A will not be freed from memory), and only free the reference from within Class $memory atribute and now counted 1, since the reference is still held by $mem.
 *   unset($mem);                    // Now the object may be freed because $mem was the strong reference that holds the last reference to the object.
 *   ```
 *   **This breaks the intended weak reference lifecycle** setup fo this class because the object will stay alive as long as `$mem` holds the reference.
 * 
 * - **Only use `WeakReference::get()` directly when working within the weak reference wrapper.**
 *   The intended behavior is to manipulate the object directly using the weak reference itself **without creating a strong reference**. 
 *   This ensures that the object can be freed as soon as it is no longer in use, which is crucial for efficient memory management, especially when working with low-level memory.
 * 
 *   **Example of correct usage:**
 *   Manipulate the object using the weak reference wrapper without adding a strong reference:
 *   ```php
 *   $weakRefMemory = MemoryStream::new();
 *   $weakRefMemory->get()->write(file_get_contents($file));
 *   $weakRefMemory->get()[0] = 0;  // Assign byte value 0 to offset 0
 *   $weakRefMemory->get()->destroy();  // Frees memory when done
 *   ```
 *   Here, the object is only weakly referenced, and it will be freed when no other strong references remain. The `destroy()` method will work as expected.
 * 
 * **Key Rule:**
 * Do **not** assign the object referenced by a weak reference to any new variable or hold a strong reference to it after calling `get()`. This will interfere with the intended memory management and prevent the object from being freed when it's no longer needed.
 * 
 * @package MemoryManager
 */
abstract class Memory implements \ArrayAccess
{
    protected $stream;

    /**
     * A hard reference of current object, this only used on when this object was used on weak reference setup, 
     *
     * @var Memory
     */
    public Memory $memory;

    /**
     * Memory constructor.
     * 
     * Initializes a memory stream based on the subclass's implementation.
     * Derived classes will specify the type of stream they need (e.g., `php://memory` or `php://temp`).
     *     
     * @throws IOException If the stream cannot be opened
     */
    public function __construct()
    {
        $this->openStream();
        // $this->memory = $this;
    }

    /**
     * Create and return a Weak reference wrapper that contains a new Memory object
     *
     * @return WeakReference<Memory>
     */
    public static function new(): WeakReference
    {
        $memory = new static();
        $memory->memory = $memory;
        return WeakReference::create($memory);
    }

    /**
     * Destroy the hard reference of the object
     *
     * @return void
     */
    public function destroy(): void
    {
        unset($this->memory);
    }

    /**
     * Open the stream for the memory storage.
     * 
     * This method is implemented in each subclass to specify the stream type (`php://memory`, `php://temp`, etc.)
     * 
     * @throws IOException If the stream cannot be opened
     */
    abstract protected function openStream(): void;

    /**
     * Write data into the memory stream using array of decimal bytes.
     * 
     * This method accepts an array of bytes 8 bit (e.g., values between 0 - 255 per byte) and writes them to the memory stream.
     * 
     * @param array $data The byte array to write into the memory stream. Each element should be an integer in the range 0-255.
     * @return bool Returns `true` if the data was written successfully, otherwise `false`.
     */
    public function writeBytes(array $data): bool
    {
        return $this->write(pack('C*', ...$data));
    }

    /**
     * Write data into the memory stream using binary data.
     * 
     * This method accepts binary data in string and writes it to the memory stream.
     * 
     * @param string $binaryData The binary data to write into the memory stream.
     * @return bool Returns `true` if the data was written successfully, otherwise `false`.
     */
    public function write(string $binaryData): bool
    {
        return fwrite($this->stream, $binaryData) !== false;
    }

    /**
     * Read data from the memory stream starting from a specific offset.
     * 
     * This method reads data from the memory stream starting at the provided offset and returns it as a string.
     * The stream will be rewound to the start of the offset before reading the data.
     * 
     * @param int $lengthBytes The number of bytes to read from the stream. Defaults to `0` to read the entire stream.
     * @param int $offset The offset from where to start reading. Defaults to `0` (start of the stream).
     * @return string|false Returns a string containing the data read from the memory stream, or `false` on failure.
     */
    public function read(int $lengthBytes = 0, int $offset = 0)
    {
        fseek($this->stream, $offset);

        if ($lengthBytes === 0) {
            $lengthBytes = $this->getStreamLength() - $offset;
        }

        return fread($this->stream, $lengthBytes);
    }

    /**
     * Read data from the memory stream starting from a specific offset as an array of bytes.
     * 
     * This method reads data from the memory stream starting at the provided offset and converts it into an array of byte values (0-255).
     * The stream will be rewound to the start of the offset before reading.
     * 
     * @param int $lengthBytes The number of bytes to read from the stream. Defaults to `0` to read the entire stream.
     * @param int $offset The offset from where to start reading. Defaults to `0` (start of the stream).
     * @return array|false Returns an array of bytes (0-255), or `false` on failure.
     */
    public function readBytes(int $lengthBytes = 0, int $offset = 0): array
    {
        $data = $this->read($lengthBytes, $offset);
        return $data !== false ? array_map('ord', str_split($data)) : false;
    }

    /**
     * Get the length of the stream.
     * 
     * @return int The length of the stream in bytes.
     */
    protected function getStreamLength(): int
    {
        $currentPos = ftell($this->stream);
        fseek($this->stream, 0, SEEK_END);
        $length = ftell($this->stream);
        fseek($this->stream, $currentPos, SEEK_SET);
        return $length;
    }

    public function __destruct()
    {
        fclose($this->stream);
        unset($this->memory);
    }

    /**
     * Implement ArrayAccess Interface
     */

    public function offsetExists($offset): bool
    {
        fseek($this->stream, $offset, SEEK_SET);
        return fread($this->stream, 1) !== false;
    }

    public function offsetGet($offset): ?int
    {
        fseek($this->stream, $offset, SEEK_SET);
        $byte = fread($this->stream, 1);
        return $byte !== false ? ord($byte) : null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_int($value) && $value >= 0 && $value <= 255) {
            fseek($this->stream, $offset, SEEK_SET);
            fwrite($this->stream, chr($value));
        } else {
            throw new \InvalidArgumentException('Value must be an integer between 0 and 255');
        }
    }

    public function offsetUnset($offset): void
    {
        // Unset isn't really applicable in a stream, but we can simply return without doing anything.
    }
}