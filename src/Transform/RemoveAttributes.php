<?php
namespace SimpleHtml\Transform;

/*
 * Unlikely\Import\Transform\RemoveAttributes
 *
 * @description Removes listed attributes
 *
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
class RemoveAttributes implements TransformInterface
{
    const DESCRIPTION = 'Remove listed attributes as per config settings';
    /**
     * Removes listed attributes
     *
     * @param string $html : HTML string to be cleaned
     * @param array $params : ['attributes' => [array,of,attributes,to,remove]]
     * @return string $html : HTML with listed attributes removed
     */
    public function __invoke(string $html, array $params = []) : string
    {
        $list  = $params['attributes'] ?? [];
        $blank = '!\b%s=".+?"!';
        $html  = $this->doReplace($list, $blank, $html);
        $blank = "!\b%s='.+?'!";
        $html  = $this->doReplace($list, $blank, $html);
        $blank = '!\b%s=.+?\b!';
        $html  = $this->doReplace($list, $blank, $html);
        return $html;
    }
    /**
     * Performs actual replacements
     *
     * @param array  $list    : array of attributes to be scrubbed
     * @param string $pattern : regex to be used
     * @param string $html    : HTML to be cleaned
     * @return string $html   : cleaned HTML
     */
    protected function doReplace(array $list, string $pattern, string $html)
    {
        foreach ($list as $attrib) {
            $patt = sprintf($pattern, $attrib);
            $html = preg_replace($patt, ' ', $html);
            $html = str_replace('  ',' ',$html);
        }
        $html = str_replace('  ',' ',$html);
        $html = str_replace(' >','>',$html);
        return $html;
    }
}