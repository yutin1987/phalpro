<?php
namespace Phalpro;

/**
 * Page
 *
 * @category Page
 * @package  Phalpro
 * @author   YuTin <yuting1987@gmail.com>
 */
class Page
{
    public $current;

    public $size;

    /**
     * __construct
     * 
     * @param integer $size    page size
     * @param integer $current page current
     */
    public function __construct($size, $current = 1)
    {
        if ($current < 1) {
            $current = 1;
        }

        if ($size < 1) {
            $size = 30;
        }

        $this->current = $current;
        $this->size    = $size;
    }

    /**
     * Get Offset
     * 
     * @return integer
     */
    public function offset()
    {
        return (($this->current - 1) * $this->size);
    }
}
?>