<?php

namespace NeiAozora\MemoryManager;

/**
 * Abstract Class Memory
 * 
 * A base class for managing memory streams. It abstracts the logic for opening, managing, and closing streams.
 * The derived classes will specify the type of stream (memory or temp) to be used.
 * 
 * This class manages the creation of weak references for memory management.
 * 
 * @package MemoryManager
 */
abstract class Memory
{
    protected $stream;

    /**
     * Memory constructor.
     * 
     * Initializes a memory stream based on the subclass's implementation.
     * Derived classes will specify the type of stream they need (e.g., `php://memory` or `php://temp`).
     * 
     * This constructor also creates a weak reference to the current instance.
     * 
     * @throws Exception If the stream cannot be opened
     */
    public function __construct()
    {
        $this->openStream();
        \WeakReference::create($this);
    }

    /**
     * Open the stream for the memory storage.
     * 
     * This method is implemented in each subclass to specify the stream type (`php://memory`, `php://temp`, etc.)
     * 
     * @throws IOException If the stream cannot be opened
     */
    abstract protected function openStream();

    /**
     * Write data into the memory stream using array of decimal bytes.
     * 
     * This method accepts an array of bytes 8 bit (e.g., values between 0 - 255 per byte) and writes them to the memory stream.
     * 
     * @param array $data The byte array to write into the memory stream. Each element should be an integer in the range 0-255.
     * @return bool Returns `true` if the data was written successfully, otherwise `false`.
     */
    public function writeBytes(array &$data)
    {
        $binaryData = pack('C*', ...$data);
        return fwrite($this->stream, $binaryData) !== false;
    }

    /**
     * Write data into the memory stream using binary data.
     * 
     * This method accepts an binary data in string (don't ask me why string, ask the PHP devs, for PHP quirks) and writes it to the memory stream.
     * 
     * @param array $data The byte array to write into the memory stream. Each element should be an integer in the range 0-255.
     * @return bool Returns `true` if the data was written successfully, otherwise `false`.
     */
    public function write(string $binaryData)
    {
        return fwrite($this->stream, $binaryData) !== false;
    }

    /**
     * Read data from the memory stream starting from a specific offset.
     * 
     * This method reads data from the memory stream starting at the provided offset and returns it as a string.
     * The stream will be rewound to the start of the offset before reading the data.
     * 
     * @param integer $lengthBytes The number of bytes to read from the stream. Defaults to `0` to read the entire stream.
     * @param integer $offset The offset from where to start reading. Defaults to `0` (start of the stream).
     * @return string|false Returns a string containing the data read from the memory stream, or `false` on failure.
     */
    public function read(int $lengthBytes = 0, int $offset = 0)
    {
        // Rewind to the start of the stream (or to the specific offset if provided)
        fseek($this->stream, $offset);  

        // If no length is provided, calculate the stream's total size
        if ($lengthBytes == 0) {
            $currentPos = ftell($this->stream);
            fseek($this->stream, 0, SEEK_END);
            $lengthBytes = ftell($this->stream) - $offset;  // Subtract offset to get the remaining length
            fseek($this->stream, $currentPos, SEEK_SET);
        }

        // Read the requested number of bytes from the stream
        return fread($this->stream, $lengthBytes);
    }


    /**
     * Read data from the memory stream starting from a specific offset as an array of bytes.
     * 
     * This method reads data from the memory stream starting at the provided offset and converts it into an array of byte values (0-255).
     * The stream will be rewound to the start of the offset before reading.
     * 
     * @param integer $lengthBytes The number of bytes to read from the stream. Defaults to `0` to read the entire stream.
     * @param integer $offset The offset from where to start reading. Defaults to `0` (start of the stream).
     * @return array|false Returns an array of bytes (0-255), or `false` on failure.
     */
    public function readBytes(int $lengthBytes = 0, int $offset = 0): array
    {
        // Rewind to the start of the stream (or to the specific offset if provided)
        fseek($this->stream, $offset);  

        // If no length is provided, calculate the stream's total size
        if ($lengthBytes == 0) {
            $currentPos = ftell($this->stream);
            fseek($this->stream, 0, SEEK_END);
            $lengthBytes = ftell($this->stream) - $offset;  // Subtract offset to get the remaining length
            fseek($this->stream, $currentPos, SEEK_SET);
        }

        // Read the requested number of bytes from the stream
        $data = fread($this->stream, $lengthBytes);
        
        if ($data === false) {
            return false;
        }

        // Convert the string to an array of byte values (0-255)
        return array_map('ord', str_split($data));
    }


    /**
     * Close the memory stream.
     * 
     * This method ensures that the memory stream is closed properly. It is automatically called by the destructor.
     * After closing, the memory or file resource is released.
     */
    public function close()
    {
        fclose($this->stream);
    }
}

?>
