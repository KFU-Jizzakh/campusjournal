<?php

use App\Services\Jats\HtmlToJatsConverter;

beforeEach(fn () => $this->conv = new HtmlToJatsConverter);

test('paragraphs become <p>', function () {
    $out = $this->conv->convert('<p>Hello world</p>');
    expect($out)->toContain('<p>Hello world</p>');
});

test('h2 becomes sec with title containing following content', function () {
    $out = $this->conv->convert('<h2>Section</h2><p>Body.</p>');
    expect($out)->toContain('<sec><title>Section</title><p>Body.</p></sec>');
});

test('h2 h3 produces nested sec', function () {
    $out = $this->conv->convert('<h2>A</h2><p>a</p><h3>B</h3><p>b</p>');
    expect($out)->toContain('<sec><title>A</title><p>a</p><sec><title>B</title><p>b</p></sec></sec>');
});

test('second h2 closes h3 and creates sibling', function () {
    $out = $this->conv->convert('<h2>A</h2><h3>B</h3><h2>C</h2>');
    expect($out)->toContain('<sec><title>A</title><sec><title>B</title></sec></sec><sec><title>C</title></sec>');
});

test('higher-level heading after lower closes it', function () {
    $out = $this->conv->convert('<h3>A</h3><h2>B</h2>');
    expect($out)->toContain('<sec><title>A</title></sec><sec><title>B</title></sec>');
});

test('content before first heading stays at body level', function () {
    $out = $this->conv->convert('<p>intro</p><h2>A</h2><p>body</p>');
    expect($out)->toContain('<body><p>intro</p><sec><title>A</title><p>body</p></sec></body>');
});

test('two sibling h2 produce two sibling sec', function () {
    $out = $this->conv->convert('<h2>A</h2><h2>B</h2>');
    expect($out)->toContain('<sec><title>A</title></sec><sec><title>B</title></sec>');
});

test('links become ext-link', function () {
    $out = $this->conv->convert('<p>See <a href="https://example.com">here</a>.</p>');
    expect($out)->toContain('<ext-link ext-link-type="uri" xlink:href="https://example.com">here</ext-link>');
});

test('lists become <list>', function () {
    $out = $this->conv->convert('<ul><li>one</li><li>two</li></ul>');
    expect($out)->toContain('<list list-type="bullet">');
    expect($out)->toContain('<list-item><p>one</p></list-item>');
    expect($out)->toContain('<list-item><p>two</p></list-item>');
});

test('ordered lists get order type', function () {
    $out = $this->conv->convert('<ol><li>a</li></ol>');
    expect($out)->toContain('<list list-type="order">');
});

test('strong and em map to bold and italic', function () {
    $out = $this->conv->convert('<p><strong>B</strong> <em>I</em></p>');
    expect($out)->toContain('<bold>B</bold>');
    expect($out)->toContain('<italic>I</italic>');
});

test('unknown tags are unwrapped', function () {
    $out = $this->conv->convert('<div><p>Kept</p></div>');
    expect($out)->toContain('<p>Kept</p>');
});

test('empty input returns self-closing body', function () {
    expect($this->conv->convert(''))->toBe('<body/>');
    expect($this->conv->convert(null))->toBe('<body/>');
});

test('plain text gets wrapped in p', function () {
    $out = $this->conv->convert('just text');
    expect($out)->toContain('<p>just text</p>');
});

test('inline img inside paragraph becomes graphic', function () {
    $out = $this->conv->convert('<p>See <img src="/img/x.png" alt="x"/> here.</p>');
    expect($out)->toContain('<graphic xlink:href="/img/x.png"/>');
});
