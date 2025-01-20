<?php

namespace NeiAozora\MemoryManager;

/**
 * Class MemoryStream
 * 
 * A memory stream implementation that uses `php://memory` for memory storage.
 * This stream will remain in memory and cannot switch to a temporary file like `php://temp`.
 * The class supports writing to the stream, reading from it, and closing the stream when no longer needed.
 * This Class is WeakReferenced by default when the object is instantiated.
 * 
 * @package MemoryManager
 */
class MemoryStream extends Memory
{
    /**
     * Open the memory stream with `php://memory`.
     * 
     * This implementation opens the `php://memory` stream, which stays in memory regardless of the data size.
     * 
     * @throws IOException If the memory stream cannot be opened
     */
    protected function openStream()
    {
        $this->stream = fopen('php://memory', 'wb+');

        if ($this->stream === false) {
            throw new IOException("Failed to open memory stream.");
        }
    }
}
?>
