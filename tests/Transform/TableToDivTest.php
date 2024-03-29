<?php
namespace FileCMSTest\Transform;

use FileCMS\Common\Transform\TransformInterface;
use FileCMS\Transform\TableToDiv;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
class TableToDivTest extends TestCase
{
    public $transform = NULL;
    public function setUp() : void
    {
        $this->transform = new TableToDiv();
    }
    public function testImplementsTransformInterface()
    {
        $expected = TRUE;
        $actual = ($this->transform instanceof TransformInterface);
        $this->assertEquals($expected, $actual, 'Class does not implement TransformInterface');
    }
    public function testMagicInvoke()
    {
        $transform = new TableToDiv();
        $expected = TRUE;
        $actual   = is_callable($transform);
        $this->assertEquals($expected, $actual, 'Class is not callable');
    }
    public function testInitTakesDefaultsIfNoParamsSupplied()
    {
        $transform = new TableToDiv();
        $transform->init([]);
        $expected = [TableToDiv::DEFAULT_TR, TableToDiv::DEFAULT_TD, TableToDiv::DEFAULT_TH];
        $actual   = [$transform->tr, $transform->td, $transform->th];
        $this->assertEquals($expected, $actual, 'Init assigns defaults if no params');
    }
    public function testRemoveTableTags()
    {
        $transform = new TableToDiv();
        $params = ['row' => 'row', 'col' => 'col-md-%d', 'width' => 12];
        $html = '<p><table><tr><th>Item 1</th><td>111111</td></tr></table></p>';
        $transform->init($params);
        $expected = '<p><tr><th>Item 1</th><td>111111</td></tr></p>';
        $actual = $transform->removeTableTags($html);
        $this->assertEquals($expected, $actual, 'HTML table tags not removed');
    }
    public function testRemoveTbodyTags()
    {
        $transform = new TableToDiv();
        $params = ['row' => 'row', 'col' => 'col-md-%d', 'width' => 12];
        $html = '<p><table><tbody><tr><th>Item 1</th><td>111111</td></tr></tbody></table></p>';
        $transform->init($params);
        $expected = '<p><tr><th>Item 1</th><td>111111</td></tr></p>';
        $actual = $transform->removeTableTags($html);
        $this->assertEquals($expected, $actual, 'HTML table tags not removed');
    }
    public function testRemoveTbodyAndTheadTags()
    {
        $transform = new TableToDiv();
        $params = ['row' => 'row', 'col' => 'col-md-%d', 'width' => 12];
        $html = '<p><table><thead><tr><th>Item</th><th>Notes</th></tr></thead><tbody><tr><th>Item 1</th><td>111111</td></tr></tbody></table></p>';
        $transform->init($params);
        $expected = '<p><tr><th>Item</th><th>Notes</th></tr><tr><th>Item 1</th><td>111111</td></tr></p>';
        $actual = $transform->removeTableTags($html);
        $this->assertEquals($expected, $actual, 'HTML table tags not removed');
    }
    public function testConvertRow()
    {
        $transform = new TableToDiv();
        $params = ['tr' => 'row', 'td' => 'col', 'th' => 'col bold'];
        $html = '<p><table><tr><th>Item 1</th><td>111111</td></tr></table></p>';
        $transform->init($params);
        $expected = '<p><table><div class="row"><th>Item 1</th><td>111111</td></div></table></p>';
        $actual = $transform->convertRow($html);
        $this->assertEquals($expected, $actual, 'HTML <tr> not converted to <div class="row">');
    }
    public function testConvertCol()
    {
        $transform = new TableToDiv();
        $params = ['tr' => 'row', 'td' => 'col', 'th' => 'col bold'];
        $html = '<p><table><tr><th>Item 1</th><td>111111</td></tr></table></p>';
        $transform->init($params);
        $expected = '<p><table><tr><div class="col bold">Item 1</div><div class="col">111111</div></tr></table></p>';
        $actual = $transform->convertCol($html);
        $this->assertEquals($expected, $actual, 'HTML <td> not converted to <div class="col">');
    }
    public function testConvertTranformsTable()
    {
        $transform = new TableToDiv();
        $params = ['tr' => 'row', 'td' => 'col', 'th' => 'col bold'];
        $html = '<p><table><tr><th>Item 1</th><td>111111</td></tr></table></p>';
        $transform->init($params);
        $expected = '<p><div class="row"><div class="col bold">Item 1</div><div class="col">111111</div></div></p>';
        $actual = $transform->convert($html);
        $this->assertEquals($expected, $actual);
    }
    public function testInvokeTranformsTable()
    {
        $transform = new TableToDiv();
        $params = ['tr' => 'row', 'td' => 'col', 'th' => 'col bold'];
        $html = '<p><table><tr><th>Item 1</th><td>111111</td></tr></table></p>';
        $expected = '<p><div class="row"><div class="col bold">Item 1</div><div class="col">111111</div></div></p>';
        $actual = $transform->__invoke($html, $params);
        $this->assertEquals($expected, $actual);
    }
}
