<?php

class AppTest extends TestCase
{
    public function testGet()
    {
        $app = $this->app();
        $app2 = $this->app();

        $this->assertEquals($app, $app2);
    }

    public function testInstance()
    {
        $app = $this->newApp();
        $className = 'arbitrary.class.name';
        $obj = $this->randomObject();
        $app->instance($className, $obj);
        $instance = $app->make($className);
        $this->assertEquals($obj, $instance);
    }

    public function testSingleton()
    {
        $app = $this->newApp();
        $className = 'arbitrary.class.name';
        $this->counter = 0;
        $obj = $this->randomObject();
        $singleton = function () use ($obj) {
            ++$this->counter;

            return $obj;
        };
        $app->singleton($className, $singleton);

        // Running make twice should have no effect.
        $instance = $app->make($className);
        $this->assertEquals($obj, $instance);
        $this->assertEquals(1, $this->counter);

        $instance = $app->make($className);
        $this->assertEquals($obj, $instance);
        $this->assertEquals(1, $this->counter);
    }

    /**
     * @return stdClass
     */
    private function randomObject()
    {
        $obj = new stdClass();
        $obj->test = rand();

        return $obj;
    }
}
