<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    /** 리소스 설정 로드 */
    protected function cfg(string $resource): array
    {
        $cfg = config("admin.$resource");
        abort_unless($cfg, 404, '존재하지 않는 관리 항목입니다.');
        $cfg['key'] = $resource;

        return $cfg;
    }

    /** select 옵션 해석 (정적 options + options_from 동적 조회). $item: 편집중 항목(자기참조 제외용) */
    protected function options(array $cfg, $item = null): array
    {
        $out = [];
        foreach ($cfg['fields'] as $f) {
            if (($f['type'] ?? '') !== 'select') {
                continue;
            }
            if (isset($f['options'])) {
                $out[$f['name']] = $f['options'];
            } elseif (isset($f['options_from'])) {
                $of = $f['options_from'];
                $query = $of['model']::orderBy($of['order'] ?? $of['key']);
                // 같은 모델을 참조하는 경우(예: 상위 카테고리) 자기 자신 제외 → 순환 방지
                if ($item && $item->exists && $of['model'] === $cfg['model']) {
                    $query->where($of['key'], '!=', $item->getKey());
                }
                $out[$f['name']] = $query->get()->pluck($of['label'], $of['key'])->toArray();
            }
        }

        return $out;
    }

    public function index(string $resource)
    {
        $cfg = $this->cfg($resource);
        $query = $cfg['model']::query();
        if (! empty($cfg['with'])) {
            $query->with($cfg['with']);
        }
        if ($search = request('q')) {
            $cols = array_keys($cfg['columns']);
            $query->where(function ($w) use ($cols, $search) {
                foreach ($cols as $c) {
                    if (! str_contains($c, '.')) {
                        $w->orWhere($c, 'like', "%{$search}%");
                    }
                }
            });
        }

        // 이미지 없는 상품만 (thumbnail 컬럼이 있는 리소스 한정)
        if (request()->boolean('no_image')
            && \Illuminate\Support\Facades\Schema::hasColumn((new $cfg['model'])->getTable(), 'thumbnail')) {
            $query->where(function ($w) {
                $w->whereNull('thumbnail')->orWhere('thumbnail', '');
            });
        }
        [$col, $dir] = $cfg['order'] ?? ['id', 'desc'];

        // 트리형(카테고리 등): 검색이 없으면 계층 정렬로 전체 표시
        if (! empty($cfg['tree']) && ! $search) {
            $all = $query->orderBy($col, $dir)->get();
            $items = $this->buildTree($all);
        } else {
            $items = $query->orderBy($col, $dir)->paginate(15)->withQueryString();
        }

        return view('admin.index', compact('cfg', 'items'));
    }

    /** parent_id 기준 계층 정렬 + _depth 부여 (부모 바로 아래 자식) */
    protected function buildTree($all)
    {
        $ordered = collect();
        $walk = function ($parentId, $depth) use (&$walk, $all, $ordered) {
            $kids = $all->filter(fn ($n) => is_null($parentId)
                ? is_null($n->parent_id)
                : (int) $n->parent_id === (int) $parentId);
            foreach ($kids as $node) {
                $node->_depth = $depth;
                $ordered->push($node);
                $walk((int) $node->id, $depth + 1);
            }
        };
        $walk(null, 0);

        return $ordered;
    }

    public function create(string $resource)
    {
        $cfg = $this->cfg($resource);
        $item = new $cfg['model'];
        foreach ($cfg['defaults'] ?? [] as $k => $v) {
            $item->{$k} = $v === 'now' ? now() : $v;
        }

        return view('admin.form', [
            'cfg' => $cfg, 'item' => $item, 'options' => $this->options($cfg, null), 'editing' => false,
        ]);
    }

    public function store(string $resource, Request $request)
    {
        $cfg = $this->cfg($resource);
        $data = $this->validateData($cfg, $request);
        $cfg['model']::create($data);

        return redirect()->route('admin.index', $resource)->with('ok', "{$cfg['label']} 항목이 등록되었습니다.");
    }

    public function edit(string $resource, $id)
    {
        $cfg = $this->cfg($resource);
        $item = $cfg['model']::findOrFail($id);

        return view('admin.form', [
            'cfg' => $cfg, 'item' => $item, 'options' => $this->options($cfg, $item), 'editing' => true,
        ]);
    }

    public function update(string $resource, $id, Request $request)
    {
        $cfg = $this->cfg($resource);
        $item = $cfg['model']::findOrFail($id);
        $item->update($this->validateData($cfg, $request));

        return redirect()->route('admin.index', $resource)->with('ok', "{$cfg['label']} 항목이 수정되었습니다.");
    }

    public function destroy(string $resource, $id)
    {
        $cfg = $this->cfg($resource);
        $cfg['model']::findOrFail($id)->delete();

        return redirect()->route('admin.index', $resource)->with('ok', "{$cfg['label']} 항목이 삭제되었습니다.");
    }

    /** 필드 정의로부터 검증 + 값 추출 */
    protected function validateData(array $cfg, Request $request): array
    {
        $rules = [];
        $imageFields = [];
        foreach ($cfg['fields'] as $f) {
            $type = $f['type'] ?? 'text';
            if ($type === 'image') {        // 파일은 별도 처리
                $imageFields[] = $f['name'];

                continue;
            }
            $r = [($f['required'] ?? false) ? 'required' : 'nullable'];
            match ($type) {
                'number' => $r[] = 'numeric',
                'date', 'datetime' => $r[] = 'date',
                'checkbox' => $r = ['nullable'],
                'select' => isset($f['options']) ? $r[] = Rule::in(array_keys($f['options'])) : null,
                default => null,
            };
            $rules[$f['name']] = $r;
        }
        $validated = $request->validate($rules);

        // checkbox 는 boolean 으로 강제
        foreach ($cfg['fields'] as $f) {
            if (($f['type'] ?? '') === 'checkbox') {
                $validated[$f['name']] = $request->boolean($f['name']);
            }
        }

        // 이미지 업로드 처리: 새 파일=교체 / 삭제체크=null / 둘 다 없음=기존 유지(키 미설정)
        foreach ($imageFields as $name) {
            if ($request->hasFile($name)) {
                $request->validate([$name => ['image', 'max:4096']]); // 4MB
                $path = $request->file($name)->store('uploads/'.$cfg['key'], 'public');
                $validated[$name] = asset('storage/'.$path);
            } elseif ($request->boolean($name.'_clear')) {
                $validated[$name] = null;
            }
        }

        return $validated;
    }
}
