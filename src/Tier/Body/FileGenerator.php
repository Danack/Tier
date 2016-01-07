<?php


namespace Tier\Body;

interface FileGenerator
{
    /**
     * Generate the file and return the filename
     * @return string
     */
    public function generate();

    /**
     * Return the last modified time in Unix epoch time
     * @return int
     */
    public function getModifiedTime();
}
