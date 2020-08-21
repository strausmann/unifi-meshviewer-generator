<?php

use PHPUnit\Framework\TestCase;
use ISPServerfarm\UnifiMeshviewer;

final class MeshviewerGeneratorTest extends TestCase
{
    private $ba;

    protected function setUp()
    {
        $this->ba = new ISPServerfarm\UnifiMeshviewer\MeshviewerGenerator;
    }
}
