<?php

/*
 * Copyright 2024 Rushlow Development - Jesse Rushlow
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace RD\SerializeTypeBundle\Exception;

/**
 * @author Jesse Rushlow<jr@rushlow.dev>
 *
 * @internal
 *
 * @experimental
 */
final class UnserializeException extends \RuntimeException
{
    public function __construct(string $error, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'Unable to convert string to object. Error: %s',
            $error
        );

        parent::__construct(message: $message, previous: $previous);
    }
}
