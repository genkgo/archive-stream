# ArchiveStream Message Body (PSR-7)

Stream a ZIP file (memory efficient) as a PSR-7 message.

![workflow code check](https://github.com/genkgo/archive-stream/workflows/code%20check/badge.svg)

## Installation

Use composer to add the package to your dependencies. Support PHP versions: 7.4, 8.0, 8.1, 8.2.
```sh
composer require genkgo/archive-stream
```

For PHP 7.3, use version 3.1.x or lower.
```sh
composer require genkgo/archive-stream@3.0.3
```

## Getting Started

```php
<?php
use Genkgo\ArchiveStream\Archive;
use Genkgo\ArchiveStream\CallbackContents;
use Genkgo\ArchiveStream\CallbackStringContent;
use Genkgo\ArchiveStream\EmptyDirectory;
use Genkgo\ArchiveStream\FileContent;
use Genkgo\ArchiveStream\Psr7Stream;
use Genkgo\ArchiveStream\StringContent;
use Genkgo\ArchiveStream\TarGzReader;
use Genkgo\ArchiveStream\TarReader;
use Genkgo\ArchiveStream\ZipReader;

$archive = (new Archive())
    ->withContent(new CallbackStringContent('callback.txt', function () {
        return 'data';
    }))
    ->withContent(new StringContent('string.txt', 'data'))
    ->withContent(new FileContent('file.txt', 'local/file/name.txt'))
    ->withContent(new EmptyDirectory('directory'))
    ->withContents([new StringContent('string2.txt', 'data')])
    ->withContents(new CallbackContents(fn () => yield new StringContent('string3.txt', 'data')));

$response = $response->withBody(
    new Psr7Stream(new ZipReader($archive))
);

// or for tar files

$response = $response->withBody(
    new Psr7Stream(new TarReader($archive))
);

// or for tar.gz files

$response = $response->withBody(
    new Psr7Stream(new TarGzReader(new TarReader($archive)))
);
```

### Usage in Symfony HttpFoundation (Symfony and Laravel)

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

$stream = new Psr7Stream(new ZipReader($archive));

$response = new StreamedResponse(function () use ($stream) {
    while ($stream->eof() === false) {
        echo $stream->read($blockSize = 1048576);
    }
}, 200, [
    'Content-type' => 'application/zip',
    'Content-Disposition' => 'attachment; filename="file.zip"',
    'Content-Transfer-Encoding' => 'binary',
]);
```

## Requirements

  * PHP >=7.3.0
  * gmp extension
  * psr/http-message

## Limitations

 * Only the Zip64 (version 4.5 of the Zip specification) format is supported.
 * Files cannot be resumed if a download fails before finishing.

## Contributors
- Paul Duncan - Original author
- Daniel Bergey
- Andy Blyler
- Tony Blyler
- Andrew Borek
- Rafael Corral
- John Maguire
- Frederik Bosch

## License

Original work Copyright 2007-2009 Paul Duncan <pabs@pablotron.org>
Modified work Copyright 2013-2015 Barracuda Networks, Inc.
Modified work Copyright 2016 Genkgo BV.

Licensed under the MIT License
