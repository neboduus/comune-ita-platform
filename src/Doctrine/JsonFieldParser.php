<?php


namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\QueryException;

class JsonFieldParser extends FunctionNode
{
  private $field;
  private $jsonField = '';

  public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
  {
    $fieldCast = explode(',', $this->jsonField);
    $field = $fieldCast[0];

    return $this->field->dispatch($sqlWalker).$this->explodeJsonField($field);
  }

  private function explodeJsonField($field)
  {
    $fieldParts = explode('.', $field);
    $jsonField = array_pop($fieldParts);
    $jsonKeys = count($fieldParts) > 0 ? " -> ".implode("' -> '", $fieldParts).'\'' : '';

    return $jsonKeys." ->> '{$jsonField}'";
  }

  public function parse(\Doctrine\ORM\Query\Parser $parser)
  {
    $parser->match(Lexer::T_IDENTIFIER);
    $parser->match(Lexer::T_OPEN_PARENTHESIS);
    $this->field = $parser->StringPrimary();
    $path = true;
    while ($path) {
      $this->jsonField .= $parser->getLexer()->lookahead['value'];
      $parser->getLexer()->moveNext();
      try {
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        $path = false;
      } catch (QueryException $e) {
      }
    }
  }
}
