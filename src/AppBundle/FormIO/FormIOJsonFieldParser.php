<?php

namespace AppBundle\FormIO;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\QueryException;

class FormIOJsonFieldParser extends FunctionNode
{
  private $field;
  private $jsonField = '';

  public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
  {
    $fieldCast = explode(',', $this->jsonField);
    $field = $fieldCast[0];
    $sql = $this->field->dispatch($sqlWalker).$this->explodeJsonField($field);
    if (isset($fieldCast[1])) {
      switch (trim($fieldCast[1])){
        case 'DECIMAL':
          $sql = "CAST(COALESCE(NULLIF(NULLIF(REGEXP_REPLACE(REPLACE($sql,',','.'), '[^0-9.]+', '', 'g'), ''), '.'), '0') AS DECIMAL)";
          break;
      }
    }
    return $sql;
  }

  private function explodeJsonField($field)
  {
    $fieldParts = explode('.', $field);
    $jsonField = array_pop($fieldParts);
    $jsonKeys = count($fieldParts) > 0 ? " -> 'data' -> '" . implode("' -> 'data' -> '", $fieldParts) . '\'' : '';

    return $jsonKeys . " -> 'data' ->> '{$jsonField}'";
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
