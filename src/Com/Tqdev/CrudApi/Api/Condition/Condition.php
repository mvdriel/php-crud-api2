<?php

interface Condition
{
    public function and(Condition $condition): Condition;
    public function or(Condition $condition): Condition;
    public function not(Condition $condition): Condition;
    public function toString(): String;
}