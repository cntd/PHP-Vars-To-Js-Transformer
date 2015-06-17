<?php namespace Laracasts\Utilities\JavaScript;

use Exception;

class PHPToJavaScriptTransformer {

    /**
     * Namespace to nest JS vars under
     *
     * @var string
     */
    protected $namespace;

    /**
     * @var ViewBinder
     */
    protected $viewBinder;

    /**
     * Transformable types
     *
     * @var array
     */
    protected $types = [
        'String', 'Array', 'Object', 'Numeric', 'Boolean', 'Null'
    ];

    /**
     * Variable array
     *
     * @var array
     */
    protected $var = [];

    /**
     * Flag binded js variables for view
     *
     * @var bool
     */
    protected $jsIsBinded = false;

    /**
     * @param ViewBinder $viewBinder
     * @param string $namespace
     */
    function __construct(ViewBinder $viewBinder, $namespace = 'window')
    {
        $this->viewBinder = $viewBinder;
        $this->namespace = $namespace;
    }

    /**
     * Bind given array of variables to view
     *
     * @param array $vars
     */
    public function put(array $vars)
    {
        // concatenate old with new variables
        $this->var = array_merge_recursive($this->var, $vars);

        // bind vars
        \App::bind('JavaScript.vars', function()
        {
            // variables to something JS-friendly.
            return $this->buildJavaScriptSyntax($this->var);
        });

        // This is what handles the process of binding
        // our JS vars to the view/page.
        if(!$this->jsIsBinded) {
            $this->viewBinder->bind();
            $this->jsIsBinded = true;
        }
    }

    /**
     * Translate the array of PHP vars
     * to JavaScript syntax.
     *
     * @param array $vars
     * @internal param $js
     *
     * @return array
     */
    public function buildJavaScriptSyntax(array $vars)
    {
        $js = $this->buildNamespaceDeclaration();

        foreach ($vars as $key => $value) {
            $js .= $this->buildVariableInitialization($key, $value);
        }

        return $js;
    }

    /**
     * Create the namespace that all
     * vars will be nested under.
     *
     * @return string
     */
    protected function buildNamespaceDeclaration()
    {
        return "window.{$this->namespace} = window.{$this->namespace} || {};";
    }

    /**
     * Translate a single PHP var to JS
     *
     * @param $key
     * @param $value
     *
     * @return string
     */
    protected function buildVariableInitialization($key, $value)
    {
        return "{$this->namespace}.{$key} = {$this->optimizeValueForJavaScript($value)};";
    }

    /**
     * Format a value for JavaScript
     *
     * @param $value
     *
     * @throws \Exception
     * @return bool|float|string
     */
    protected function optimizeValueForJavaScript($value)
    {
        // For every kind of type, let's see
        // if it needs to be transformed for JS
        foreach ($this->types as $transformer)
        {
            $js = $this->{"transform{$transformer}"}($value);

            if ( ! is_null($js)) return $js;
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function transformString($value)
    {
        if (is_string($value))
        {
            return "'{$this->escape($value)}'";
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function transformArray($value)
    {
        if (is_array($value))
        {
            return json_encode($value);
        }
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function transformNumeric($value)
    {
        if (is_numeric($value))
        {
            return $value;
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function transformBoolean($value)
    {
        if (is_bool($value))
        {
            return $value ? 'true' : 'false';
        }
    }

    /**
     * @param $value
     * @return string
     * @throws \Exception
     */
    protected function transformObject($value)
    {
        if (is_object($value))
        {
            // If a toJson() method exists, we'll assume that
            // the object can cast itself automatically
            if (method_exists($value, 'toJson')) return $value;

            // Otherwise, if the object doesn't even have
            // a toString method, we can't proceed.
            if ( ! method_exists($value, '__toString'))
            {
                throw new Exception('The provided object needs a __toString() method.');
            }

            return "'{$value}'";
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function transformNull($value)
    {
        if (is_null($value))
        {
            return 'null';
        }
    }

    /**
     * Escape single quotes (for now).
     * What else do we need to worry about?
     *
     * @param $value
     *
     * @return mixed
     */
    protected function escape($value)
    {
        return str_replace("'", "\\'", $value);
    }

}
