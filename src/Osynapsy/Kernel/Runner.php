<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Kernel;

use Osynapsy\Http\Request;
use Osynapsy\Kernel\Route;
use Osynapsy\Db\DbFactory;
use Osynapsy\Helper\AutoWire;

/**
 * Description of Runner
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class Runner
{
    private $request;
    private $route;
    private $autowire;

    public function __construct(Request $request, Route $route)
    {
        $this->request = $request;
        $this->route = $route;
        $this->autowire = new AutoWire([$this->route, $this->request]);
    }

    public function run()
    {
        try {
            $this->checks();
            $this->loadDatasources();
            $this->runApplicationController();
            return $this->runRouteController($this->route->controller);
        } catch (KernelException $e) {
            return $this->dispatchKernelException($e);
        } catch(\Exception $e) {
            return $this->pageOops($e->getMessage(), $e->getTrace());
        } catch(\Error $e) {
            return $this->pageOops(sprintf('%s at row %s file %s', $e->getMessage(), $e->getLine(), $e->getFile()), $e->getTrace());
        }
    }

    private function checks()
    {
        if (!$this->route->controller) {
            throw new KernelException('No route to destination ('.$this->request->get('server.REQUEST_URI').')', 404);
        }
        if (!$this->route->application) {
            throw new KernelException('No application defined', 405);
        }
    }

    private function loadDatasources()
    {
        $listDatasource = $this->request->search('db',"env.app.{$this->route->application}.datasources");
        $dbFactoryHandle = $this->autowire->getInstance(DbFactory::class);
        $this->autowire->addHandle($dbFactoryHandle);
        foreach ($listDatasource as $datasource) {
            $connectionString = $datasource['@value'];
            $dbFactoryHandle->createConnection($connectionString);
        }
        if ($dbFactoryHandle->hasConnection(0)) {
            $this->autowire->addHandle($dbFactoryHandle->getConnection(0));
        }
    }

    private function runApplicationController()
    {
        $applicationController = str_replace(':', '\\', $this->request->get("env.app.{$this->route->application}.controller"));
        if (empty($applicationController)) {
            return true;
        }
        $appController = $this->autowire->getInstance($applicationController);
        $this->autowire->addHandle($appController);
        if (!$appController->run()) {
            throw new KernelException('App not running (access denied)','501');
        }
    }

    private function runRouteController($classController)
    {
        $controller = $this->autowire->getInstance($classController);
        $this->autowire->addHandle($controller);
        return strval($controller->run());
    }

    private function dispatchKernelException(KernelException $e)
    {
        switch($e->getCode()) {
            case '404':
                return $this->pageNotFound($e->getMessage());
            default :
                return $this->pageOops($e->getMessage(), $e->getTrace());
        }
    }

    public function pageNotFound($message = 'Page not found')
    {
        ob_clean();
        header('HTTP/1.1 404 Not Found');
        return $message;
    }

    public function pageOops($message, $trace)
    {
        ob_clean();
        return strpos($_SERVER['HTTP_ACCEPT'] ?? null, 'json') === false ?
               $this->pageOopsHtml($message, $trace) :
               $this->pageOopsText($message, $trace);
    }

    public function pageOopsText($message, $traces)
    {
        $tmp = PHP_EOL."funzione %s alla riga %s del file %s";
        $result = sprintf(
             'Si è verificato il seguente errore:
              %s',
            $message
        );
        foreach ($traces as $trace) {
            $result .= sprintf($tmp,  $trace['function'] ?? '', $trace['line'] ?? '', $trace['file'] ?? '');
        }
        return $result;
    }

    public function pageOopsHtml($message, $trace)
    {
        error_log(sprintf("ERRORE APPLICATIVO: %s\nSTACK TRACE:\n%s", $message, print_r($trace, true)));
        // Comportamento originale con i dettagli completi abilitati
        $body = '';
        foreach ($trace as $step) {
            $body .= '<tr>';
            $body .= '<td>'.(!empty($step['class']) ? $step['class'] : '&nbsp;').'</td>';
            $body .= '<td>'.(!empty($step['function']) ? $step['function'] : '&nbsp;').'</td>';
            $body .= '<td>'.(!empty($step['file']) ? $step['file'] : '&nbsp;').'</td>';
            $body .= '<td>'.(!empty($step['line']) ? $step['line'] : '&nbsp;').'</td>';
            $body .= '</tr>';
        }

        return <<<PAGE
            <div class="container" style="font-family: Arial; text-align: center; margin-top: 50px;">
                    <h2 ondblclick="document.getElementById('error').style.display = 'block';">Internal Server Error</h2>
                    <p>Si è verificato un errore inaspettato. Riprova più tardi.</p>
                </div>
                <div id="error" style="display : none; text-align: center;">
                [ERRORE]
                {$message}

                [TRACE]
                <table border=1 align="center">
                {$body}
                </table>
                </div>
        PAGE;
    }
}
