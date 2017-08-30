<?php

/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */


namespace KonstantinKuklin\DoctrineCompressedFields\DqlFunction;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use KonstantinKuklin\DoctrineCompressedFields\Engine;
use KonstantinKuklin\DoctrineCompressedFields\MetadataLayer;
use ReflectionClass;

/**
 * CompressedSelectFunction ::= "BITS_MASK" ( "ArithmeticPrimary", "ArithmeticPrimary")
 */
class BitsMask extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    private $maskField;

    /**
     * @var \Doctrine\ORM\Query\AST\Literal
     */
    private $findValue;

    /**
     * @var \Doctrine\ORM\Query\ParserResult
     */
    private $parserResult;

    /**
     * @var Engine
     */
    private $engine;

    /**
     * {@inheritdoc}
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->engine = new Engine();
    }

    /**
     * @param Parser $parser
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->maskField = $parser->ArithmeticPrimary();
        $this->deleteDeferredPath($parser, $this->maskField);

        $parser->match(Lexer::T_COMMA);
        $this->findValue = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        $this->parserResult = $parser->getParserResult();
    }

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $value = $this->findValue->dispatch($sqlWalker);
        $aliasTable = $this->maskField->identificationVariable;
        $field = $this->maskField->field;

        $queryComponent = $sqlWalker->getQueryComponent($aliasTable);
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $classMetadata */
        $classMetadata = $queryComponent['metadata'];

        $maskAnnotation = MetadataLayer::getMaskAnnotation($classMetadata, $field);
        $columnAlias = $this->getColumnAlias($this->parserResult, $maskAnnotation);

        $maskReflection = MetadataLayer::getMaskReflection($classMetadata, $field);
        $valueCompressed = $this->engine->getPackedValue(
            0,
            [
                [
                    MetadataLayer::REFLECTION => $maskReflection,
                    MetadataLayer::ANNOTATION => $maskAnnotation,
                ],
            ],
            [$value]
        );

        return "( {$columnAlias} & {$valueCompressed} = {$valueCompressed} )";
    }

    /**
     * @param \Doctrine\ORM\Query\ParserResult                           $parserResult
     * @param \KonstantinKuklin\DoctrineCompressedFields\Annotation\Mask $maskAnnotation
     *
     * @return string
     */
    private function getColumnAlias($parserResult, $maskAnnotation)
    {
        $resultSetMapping = $parserResult->getResultSetMapping();
        $fieldMappings = $resultSetMapping->fieldMappings;
        $fieldMappingsFlipped = array_flip($fieldMappings);

        return $fieldMappingsFlipped[$maskAnnotation->property];
    }

    /**
     * @param Parser $parser
     * @param mixed  $object
     *
     * @throws \ReflectionException
     */
    private function deleteDeferredPath($parser, $object)
    {
        $reflectionParser = new ReflectionClass($parser);
        $deferredPathExpressionsReflection = $reflectionParser->getProperty('deferredPathExpressions');
        $deferredPathExpressionsReflection->setAccessible(true);
        $deferredPathExpressions = $deferredPathExpressionsReflection->getValue($parser);
        $hash = spl_object_hash($object);

        $deferredPathExpressions = array_filter(
            $deferredPathExpressions,
            function ($item) use ($hash) {
                return $hash !== spl_object_hash($item['expression']);
            }
        );
        $deferredPathExpressionsReflection->setValue($parser, $deferredPathExpressions);
    }
}
