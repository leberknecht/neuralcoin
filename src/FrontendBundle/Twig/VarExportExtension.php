<?php

namespace FrontendBundle\Twig;

class VarExportExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('var_export', [$this, 'varExportFilter']),
        ];
    }

    /**
     * @param $var
     * @return mixed
     */
    public function varExportFilter($var)
    {
        return var_export($var, true);
    }

}
