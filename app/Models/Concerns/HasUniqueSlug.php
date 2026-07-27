<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * 슬러그 자동 생성 + 중복 방지.
 *
 *  - 비워두면 이름에서 자동 생성 (한글만 있는 이름은 Str::slug 가 빈 문자열이 되므로
 *    모델별 접두사 + 순번으로 대체 — 기존 데이터 규칙 cat-1 / brand-1 / item-… 을 따른다)
 *  - 이미 쓰이는 값이면 -2, -3 … 을 붙이고, 그래도 막히면 짧은 임의 문자열로 마무리
 *  - 관리자 화면에는 unique 검증도 함께 걸려 있어 수동 입력 중복은 오류로 안내된다.
 *    이 훅은 임포트 커맨드·tinker 처럼 검증을 거치지 않는 경로까지 막는 안전장치다.
 */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::saving(function ($model) {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            } elseif ($model->isDirty('slug')) {
                $model->slug = $model->generateUniqueSlug((string) $model->slug);
            }
        });
    }

    /** 자동 생성 접두사 — 모델에서 재정의 */
    protected function slugPrefix(): string
    {
        return 'item';
    }

    /** 이름 기반 기본 슬러그 — 모델에서 재정의 가능 */
    protected function slugBase(): string
    {
        return (string) Str::slug((string) ($this->name ?? ''));
    }

    /** 이름에서 뽑을 게 없을 때 붙일 순번 */
    protected function slugSequence(): int
    {
        return (int) (static::query()->max($this->getKeyName()) ?? 0) + 1;
    }

    /** 중복되지 않는 슬러그 반환 */
    public function generateUniqueSlug(?string $from = null): string
    {
        $base = $from !== null ? (string) Str::slug($from) : '';
        if ($base === '') {
            $base = $this->slugBase();
        }
        if ($base === '') {
            $base = $this->slugPrefix().'-'.$this->slugSequence();
        }
        $base = trim(mb_substr($base, 0, 200), '-') ?: $this->slugPrefix();

        for ($i = 0; $i < 40; $i++) {
            $candidate = match (true) {
                $i === 0  => $base,
                $i <= 20  => $base.'-'.($i + 1),
                default   => $base.'-'.Str::lower(Str::random(5)),
            };
            if (! $this->slugTaken($candidate)) {
                return $candidate;
            }
        }

        return $base.'-'.Str::lower(Str::random(10));
    }

    /** 다른 레코드가 이미 쓰는 슬러그인지 */
    protected function slugTaken(string $slug): bool
    {
        $q = static::query()->where('slug', $slug);
        if ($this->exists) {
            $q->whereKeyNot($this->getKey());
        }

        return $q->exists();
    }
}
