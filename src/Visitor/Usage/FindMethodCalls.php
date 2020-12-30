<?php

namespace SensioLabs\DeprecationDetector\Visitor\Usage;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use SensioLabs\DeprecationDetector\FileInfo\Usage\MethodUsage;
use SensioLabs\DeprecationDetector\FileInfo\PhpFileInfo;
use SensioLabs\DeprecationDetector\Visitor\ViolationVisitorInterface;

class FindMethodCalls extends NodeVisitorAbstract implements ViolationVisitorInterface
{
    /**
     * @var string
     */
    protected $parentName;

    /**
     * @var PhpFileInfo
     */
    protected $phpFileInfo;

    /**
     * @param PhpFileInfo $phpFileInfo
     *
     * @return $this
     */
    public function setPhpFileInfo(PhpFileInfo $phpFileInfo)
    {
        $this->phpFileInfo = $phpFileInfo;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            if (isset($node->namespacedName)) {
                $this->parentName = $node->namespacedName->toString();
            } else {
                $name = isset($node->name->name) ? $node->name->name : (string)$node->name;
                $this->parentName = $name;
            }
        }

        if ($node instanceof Node\Expr\MethodCall) {
            // skips concat method names like $twig->{'get'.ucfirst($type)}()
            if ($node->name instanceof Node\Expr\BinaryOp\Concat) {
                return;
            }

            // skips variable methods like $definition->$method
            if (!is_string($node->name)) {
                return;
            }

            $type = $node->var->getAttribute('guessedType', null);

            if (null !== $type) {
                $name = isset($node->name->name) ? $node->name->name : (string)$node->name;
                $methodUsage = new MethodUsage($name, $type, $node->getLine(), false);
                $this->phpFileInfo->addMethodUsage($methodUsage);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            $this->parentName = null;
        }
    }
}
