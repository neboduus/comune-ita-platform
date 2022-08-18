<?php


namespace AppBundle\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class JsonbArrayElementsText extends FunctionNode
{
  private $expr1;

  public function getSql(SqlWalker $sqlWalker)
  {
    return sprintf(
      "JSONB_ARRAY_ELEMENTS_TEXT(%s)",
      $this->expr1->dispatch($sqlWalker)
    );
  }

  public function parse(Parser $parser)
  {
    $parser->match(Lexer::T_IDENTIFIER);
    $parser->match(Lexer::T_OPEN_PARENTHESIS);
    $this->expr1 = $parser->StringPrimary();
    $parser->match(Lexer::T_CLOSE_PARENTHESIS);
  }
}