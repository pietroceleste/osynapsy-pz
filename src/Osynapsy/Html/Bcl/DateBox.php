<?php
namespace Osynapsy\Html\Bcl;

/**
 * Description of DateBox
 *
 * @author Pietro Celeste
 */
class DateBox extends TextBox
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->att('type', 'date');
    }
}
