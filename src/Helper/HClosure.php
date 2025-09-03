<?php

namespace AndrewGos\Serializer\Helper;

use Closure;
use ReflectionFunction;

final readonly class HClosure
{
    public static function toString(Closure $closure): string
    {
        $refl = new ReflectionFunction($closure);
        $result = ($refl->getClosureScopeClass() ? $refl->getClosureScopeClass()->getName() . "::" : '')
            . $refl->getName();
        if (!$refl->isInternal()) {
            $result .= '[' . $refl->getFileName() . ':' . $refl->getStartLine() . '-' . $refl->getEndLine() . ']';
        }
        $result .= '(';
        foreach ($refl->getParameters() as $param) {
            $result .= $param->getType();
        }
        $result .= ')';
        if ($refl->hasReturnType()) {
            $result .= ': ' . $refl->getReturnType();
        } elseif ($refl->hasTentativeReturnType()) {
            $result .= ': ' . $refl->getTentativeReturnType();
        }
        return $result;
    }
}
