<?php

# This file is generated, changes you make will be lost.
# Make your changes in /compiler/lib/JIT/Call/Native.pre instead.

/*
 * This file is part of PHP-Compiler, a PHP CFG Compiler for PHP code
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCompiler\JIT\Call;

use PHPCompiler\JIT\Context;
use PHPCompiler\JIT\Call;
use PHPCompiler\JIT\Variable;

use PHPLLVM\Value;

class Native implements Call {

    public Value $function;
    public string $name;
    public array $argTypes;

    public function __construct(Value $function, string $name, array $argTypes) {
        $this->function = $function;
        $this->name = $name;
        $this->argTypes = $argTypes;
    }

    public function call(Context $context, Variable ... $args): Value {
        $argValues = [];
        foreach ($args as $index => $arg) {
            $argValues[] = $this->compileArg($context, $arg, $index);
        }
        return $context->builder->call(
            $this->function,
            ...$argValues
        );
    }

    protected function compileArg(Context $context, Variable $arg, int $argNum): Value {
        $type = $this->argTypes[$argNum];
        $typeName = $context->getStringFromType($type);
        $value = $context->helper->loadValue($arg);
        switch ($typeName) {
            case '__string__*':
                switch ($arg->type) {
                    case Variable::TYPE_STRING:
                        return $value;
                    case Variable::TYPE_VALUE:
                        $str = $this->context->builder->call(
                        $this->context->lookupFunction('__value__readString') , 
                        $value
                        
                    );
    
                        return $str;
                }
                break;
            case '__value__':
                switch ($arg->type) {
                    case Variable::TYPE_VALUE:
                        return $value;
                }
                break;
            case 'int64':
                switch ($arg->type) {
                    case Variable::TYPE_NATIVE_LONG:
                        return $value;
                    case Variable::TYPE_VALUE:
                        $int = $this->context->builder->call(
                        $this->context->lookupFunction('__value__readLong') , 
                        $value
                        
                    );
    
                        return $int;
                }
        }
        throw new \LogicException("Unsupported cast for arg type $typeName from {$arg->type}");
        return $context->helper->loadValue($arg);
    }

}