<?php

namespace NeiAozora\MemoryManager;

/**
 * Class TempMemory
 * 
 * A memory stream implementation that uses `php://temp` for temporary memory storage.
 * This stream starts by using memory but can switch to a temporary file if the data grows too large.
 * The class supports writing to the stream, reading from it, and closing the stream when no longer needed.
 * This Class is WeakReferenced by default when the object is instantiated.
 * 
 * @package MemoryManager
 */
class TempMemory extends Memory
{
    /**
     * Open the memory stream with `php://temp`.
     * 
     * This implementation opens the `php://temp` stream, which will automatically switch to a file-based 
     * storage if the data exceeds the memory limit.
     * 
     * @throws IOException If the memory stream cannot be opened
     */
    protected function openStream()
    {
        $this->stream = fopen('php://temp', 'wb+');

        if ($this->stream === false) {
            throw new IOException("Failed to open memory stream.");
        }
    }
}
?>


?>
