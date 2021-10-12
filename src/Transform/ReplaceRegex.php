<?php
namespace SimpleHtml\Transform;

/*
 * Unlikely\Import\Transform\Replace
 *
 * @description performs search and replace using preg_replace()
 * @author doug@unlikelysource.com
 * @date 2021-10-04
 * Copyright 2021 unlikelysource.com
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 * * Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the following disclaimer
 *   in the documentation and/or other materials provided with the
 *   distribution.
 * * Neither the name of the  nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
class ReplaceRegex implements TransformInterface
{
    const REPLACE_EOL = '--XXX--';
    const DESCRIPTION = 'Perform search and replace using preg_replace() based upon config settings';
    /**
     * Performs search and replace
     *
     * @param string $html  : HTML string to be cleaned
     * @param array $params : ['regex' => search regex, 'replace' => replace with this]
     * @return string $html : transformed HTML
     */
    public function __invoke(string $html, array $params = []) : string
    {
        $regex = $params['regex'] ?? '';
        $replace = $params['replace'] ?? '';
        if (!empty($regex)) {
            $html = str_replace(PHP_EOL, self::REPLACE_EOL, $html);
            $html = preg_replace($regex, $replace, $html);
            $html = str_replace(self::REPLACE_EOL, PHP_EOL, $html);
        }
        return $html;

    }
}