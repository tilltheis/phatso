<?php
/**
 * Phatso - A PHP Micro Framework
 * Copyright (C) 2008, Judd Vinet <jvinet@zeroflux.org>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * (1) The above copyright notice and this permission notice shall be
 *     included in all copies or substantial portions of the Software.
 * (2) Except as contained in this notice, the name(s) of the above
 *     copyright holders shall not be used in advertising or otherwise
 *     to promote the sale, use or other dealings in this Software
 *     without prior written authorization.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 *
 * Version 0.1 :: 2008-10-03
 *   - initial release
 * Version 0.2 :: 2009-04-30
 *   - optimizations by Woody Gilk <woody.gilk@kohanaphp.com>
 *   - auto-detect base web root for relative URLs
 * Version 0.2.1 :: 2009-05-31
 *   - bug reported by Sebastien Duquette
 * Version 0.2.2 :: 2011-02-18
 *   - bug reported by Till Theis (http://www.tilltheis.de)
 *
 */

if (!defined('DEBUG')) {
    define('DEBUG', false);
}

// if DEBUG is false do not display errors
if (DEBUG) {
    error_reporting(-1);
    ini_set('display_errors', 1);
}
else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

class Phatso
{
    var $templates_dir   = 'templates';
    var $layout = 'layout';
    var $template_vars   = array();
    var $web_root        = null;
    var $action          = null;
    var $view            = null;

    /**
     * Dispatch web request to correct function, as defined by
     * URL route array.
     *
     * @param array $urls
     */
    function run($urls)
    {
        $this->web_root = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
        $request =  substr($_SERVER['REQUEST_URI'], strlen($this->web_root) - 1);

        foreach($urls as $url => $route) {
            $url = '/^' . str_replace('/', '\/', $url) . '\/?$/i';
            if (preg_match($url, $request, $matches)) {
                $this->action = $route;
                $action_method = $this->action . 'Action';
                $params = array();
                if (!empty($matches[1])) {
                    $params = explode('/', trim($matches[1], '/'));
                }
                if (method_exists($this, $action_method)) {
                    $this->view = $this->action;
                    $this->beforeFilter();
                    call_user_func_array(array(&$this, $action_method), $params);
                    echo $this->render($this->view);
                    $this->afterFilter();
                }
                break;
            }
        }

        if ($this->action === null) {
            if (DEBUG) {
                $this->debug('Route nout found: ' . $request);
            }
            $this->status(404, 'File Not Found');
        }
    }

    /**
     * Set HTTP status code and exit if code >= 400.
     *
     * @param int $code
     * @param string $msg
     */
    function status($code, $msg) {
        header("{$_SERVER['SERVER_PROTOCOL']} $code $msg");
        if(method_exists($this, 'status'.$code)) {
            $this->view = 'status'.$code;
            call_user_func(array(&$this, 'status'.$code), $msg);
            echo $this->render($this->view);
        }
        exit;
    }

    /**
     * Redirect to a new URL
     *
     * @param string $url
     * @param int $code
     * @param string $msg
     */
    function redirect($url, $code = 302, $msg = 'Found') {
        if (!preg_match('(^http(s)?://)', $url)) {
            $url = $this->getBaseUrl() . $this->web_root . $url;
        }
        header("{$_SERVER['SERVER_PROTOCOL']} $code $msg");
        header('Location: ' . $url);
        exit;
    }

    /**
     * Set a template variable.
     *
     * @param string $name
     * @param mixed $val
     */
    function set($name, $val) {
        $this->template_vars[$name] = $val;
    }

    /**
     * Render a template and return the content.
     *
     * @param string $template_filename
     */
    function fetch($template_filename)
    {
        $template_filename .= '.php';
        ob_start();
        extract($this->template_vars, EXTR_SKIP);
        if (file_exists($this->templates_dir . DIRECTORY_SEPARATOR . $template_filename)) {
            require $this->templates_dir . DIRECTORY_SEPARATOR . $template_filename;
        }
        else {
            if (DEBUG) {
                $this->debug('File not found: ' . $this->templates_dir . DIRECTORY_SEPARATOR . $template_filename);
            }
            return;
        }
        return str_replace('/.../', $this->web_root, ob_get_clean());
    }

    /**
     * Render a template and return the content as a string
     *
     * @param string $filename
     */
    function render($filename)
    {
        $this->set('CONTENT_FOR_LAYOUT', $this->fetch($filename));
        return $this->fetch($this->layout);
    }

    /**
     * return the current url
     */
    function getBaseUrl()
    {
        $protocol = 'http://';
        $port = '';
        if (!empty($_SERVER['HTTPS'])) {
            $protocol = 'https://';
        }
        if ($_SERVER['SERVER_PORT'] != 80) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }
        return $protocol . $_SERVER['SERVER_NAME'] . $port;
    }

    /**
     * set the layout to use while rendering the page
     *
     * @param string $layout
     */

    function setLayout($layout) {
        $this->layout = $layout;
    }

    /**
     * set the view to use while rendering the page
     *
     * @param string $view
     */
    function setView($view) {
        $this->view = $view;
    }

    /**
     * debug function to dissplay a variable's value
     *
     * @param mixed $arg
     */
    function debug($arg) {
        if (DEBUG === false) return;
        $args = func_get_args();
        echo '<pre>';
        foreach($args as $arg) {
            echo '(', gettype($arg), ') ', print_r($arg, TRUE)."<br/>\n";
        }
        echo '</pre>';
    }

    /**
     * abstract method to be run before calling the action method
     */
    function beforeFilter()
    {
    }

    /**
     * abstract method to be run after calling the action method
     */
    function afterFilter()
    {
    }
}
