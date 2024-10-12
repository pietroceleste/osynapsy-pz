<?php
namespace Osynapsy\Mvc\View;

use Osynapsy\Kernel;

/**
 * Description of Template
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Template
{
    protected $controller;
    protected $buffer;
    protected $path;
    protected $repo = [
        'title' => [],
        'css' => [],
        'js' => [],
        'main' => []
    ];

    public function __construct($path = null)
    {
        $this->path = $path;
        $this->buffer = !empty($path) ? $this->includeTemplate($path) : $this->defaultBuffer();
        $this->appendFormController();
    }

    public function includeTemplate($path)
    {
        if (!is_file($path)) {
           throw new \Exception(sprintf('Template asset file %s do not exists', print_r($path,true)));
        }
        include $path;
        return ob_get_clean();
    }

    protected function defaultBuffer()
    {
        return <<<DEF
        <html>
        <head>
            <title><!--title--></title>
            <!--css-->
        </head>
        <body>
            <!--main-->
            <!--js-->
        </body>
        </html>
DEF;
    }

    /**
     * Method that add content to the response
     *
     * @param mixed $content
     * @param mixed $part
     * @param bool $checkUnique
     * @return mixed
     */
    public function addContent($content, $part = 'main', $checkUnique = false)
    {
        if ($checkUnique && !empty($this->repo[$part]) && in_array($content, $this->repo[$part])) {
            return;
        }
        $this->repo[$part][] = $content;
    }

    public function setTitle($title)
    {
        $this->addContent($title, 'title');
    }

    public function addHtml($html)
    {
        $this->addContent(strval($html));
    }

    public function addJs($path)
    {
        $this->addContent(sprintf('<script src="%s"></script>', $path), 'js', true);
    }

    public function addJsCode($code)
    {
        $this->addContent(sprintf('<script>%s</script>', PHP_EOL.$code.PHP_EOL), 'js', true);
    }

    public function addCss($path)
    {
        $this->addContent(sprintf('<link href="%s" rel="stylesheet" />', $path), 'css', true);
    }

    public function addStyle($style)
    {
        $this->addContent(sprintf('<style>%s</style>', PHP_EOL.$style.PHP_EOL), 'css', true);
    }

    public function reset()
    {
        $this->buffer = '<!--main-->';
    }

    public function appendFormController()
    {
        $this->addJs('/assets/osynapsy/'.Kernel::VERSION.'/js/FormController.js');
        $this->addCss('/assets/osynapsy/'.Kernel::VERSION.'/css/style.css');
    }

    public function __toString()
    {
        $parts = array_map(fn($subparts) => implode(PHP_EOL, $subparts), $this->repo);
        $placeholders = array_map(fn($partId) => sprintf('<!--%s-->', $partId), array_keys($parts));
        return str_replace($placeholders, array_values($parts), $this->buffer);
    }
}
