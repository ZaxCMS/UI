<?php

namespace Zax\Application\UI;

use Nette\Application\BadRequestException;

class InvalidViewNameException extends BadRequestException {}

class InvalidViewException extends BadRequestException {}

class InvalidRenderException extends BadRequestException {}
