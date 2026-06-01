<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RelatorioFuncionariosExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        private Collection $funcionarios,
        private string     $dataInicio,
        private string     $dataFim
    ) {}

    public function title(): string
    {
        return 'Relatório por Funcionário';
    }

    public function headings(): array
    {
        return [
            'Nome',
            'CPF',
            'Empresa',
            'Função / Cargo',
            'Coordenador',
            'Dias Trabalhados',
            'Entradas',
            'Saídas',
            'Total Horas',
            'Média por Turno',
        ];
    }

    public function collection(): Collection
    {
        return $this->funcionarios->map(function ($f) {
            $segundos  = (int) ($f->total_segundos ?? 0);
            $entradas  = (int) ($f->total_entradas ?? 0);
            $media     = $entradas > 0 ? intdiv($segundos, $entradas) : 0;

            return [
                $f->nome,
                $f->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $f->cpf) : '—',
                $f->empresa?->nome ?? 'Sem empresa',
                $f->funcao_cargo ?? '—',
                $f->coordenador ? 'Sim' : 'Não',
                $f->dias_trabalhados ?? 0,
                $f->total_entradas   ?? 0,
                $f->total_saidas     ?? 0,
                $this->formatarHoras($segundos),
                $this->formatarHoras($media),
            ];
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF028FD0']],
            ],
        ];
    }

    private function formatarHoras(int $segundos): string
    {
        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        return sprintf('%dh %02dm', $h, $m);
    }
}
