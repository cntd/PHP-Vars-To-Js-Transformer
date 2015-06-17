<?php namespace Laracasts\Utilities\JavaScript;

use Illuminate\Events\Dispatcher;

class LaravelViewBinder implements ViewBinder {

    /**
     * @var Dispatcher
     */
    private $event;

    /**
     * @var string
     */
    private $viewToBindVariables;

    /**
     * @param Dispatcher $event
     * @param $viewToBindVariables
     */
    function __construct(Dispatcher $event, $viewToBindVariables)
    {
        $this->event = $event;
        $this->viewToBindVariables = $viewToBindVariables;
    }

    /**
     * Bind the given JavaScript to the
     * view using Laravel event listeners
     *
     * @param $js The ready-to-go JS
     */
    public function bind()
    {
        $this->event->listen("composing: {$this->viewToBindVariables}", function()
        {
            $js = \App::make('JavaScript.vars');
            echo "<script>{$js}</script>";
        });
    }

}
