<?php
/**
 * Minimal dependency-free PDF writer.
 *
 * Produces a valid PDF 1.4 document containing Helvetica text lines, which is
 * all the reports page needs. Escapes the characters that are illegal inside a
 * PDF literal string and computes a correct xref table.
 */
class SimplePdf
{
    /** @var array<int,array{text:string,size:int,bold:bool,indent:int}> */
    private array $lines = [];

    public function add(string $text, int $size = 11, bool $bold = false, int $indent = 0): self
    {
        $this->lines[] = ['text' => $text, 'size' => $size, 'bold' => $bold, 'indent' => $indent];
        return $this;
    }

    public function rule(): self
    {
        return $this->add('----------------------------------------------------------------', 9);
    }

    public function blank(int $count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->add('', 11);
        }
        return $this;
    }

    /** Build the page content stream. */
    private function contentStream(): string
    {
        $out = "BT\n";
        $y   = 800;
        foreach ($this->lines as $line) {
            $font = $line['bold'] ? '/F2' : '/F1';
            $out .= sprintf("%s %d Tf\n", $font, $line['size']);
            $out .= sprintf("1 0 0 1 %d %d Tm\n", 50 + $line['indent'], $y);
            $out .= '(' . $this->escape($line['text']) . ") Tj\n";
            $y -= $line['size'] + 6;
            if ($y < 50) {
                break; // single page is enough for these reports
            }
        }
        $out .= "ET\n";
        return $out;
    }

    /** Escape (), \ and non-ASCII for a PDF literal string. */
    private function escape(string $s): string
    {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('(', '\\(', $s);
        $s = str_replace(')', '\\)', $s);
        // Strip anything outside printable ASCII that Helvetica cannot render
        $s = preg_replace('/[^\x20-\x7E]/', '', $s) ?? $s;
        return $s;
    }

    /** Render the complete PDF as a binary string. */
    public function render(): string
    {
        $content = $this->contentStream();

        $objects = [
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
               . "/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>",
            4 => "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream",
            5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>",
            6 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>",
        ];

        $pdf     = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$body\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $count   = count($objects) + 1;

        $pdf .= "xref\n0 $count\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $num => $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF\n";

        return $pdf;
    }
}
