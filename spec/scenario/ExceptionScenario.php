<?php
namespace spec\groupcash\bank\scenario;

use rtens\scrut\fixtures\ExceptionFixture;

class ExceptionScenario {

    /** @var ApplicationCapabilities */
    private $app;

    /** @var ExceptionFixture */
    private $except;

    public function __construct(ApplicationCapabilities $app, ExceptionFixture $except) {
        $this->app = $app;
        $this->except = $except;
    }

    function __call($name, $arguments) {
        $this->except->tryTo(function () use ($name, $arguments) {
            call_user_func_array([$this->app, $name], $arguments);
        });
    }
}