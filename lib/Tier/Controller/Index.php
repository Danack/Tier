<?php

namespace Tier\Controller;

class Index {
    public function display()
    {
        return getTemplateCallable('pages/index');
    }
}

