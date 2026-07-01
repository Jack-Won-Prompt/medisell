# 메디셀(MEDISELL) — 의료소모품 쇼핑몰

[medioc.com](https://www.medioc.com/)을 참고해 **병의원·일반 혼합형(B2B+B2C)** 의료소모품 쇼핑몰을
**Laravel 11 + MariaDB**로 새로 구축한 프로젝트입니다. 디자인은 medioc 스타일의
**네이비(#0b3d91)/화이트/레드 비즈니스 테마**, 아이콘은 커스텀 SVG 스프라이트입니다.

## 접속 주소
- XAMPP Apache: http://localhost/medisell/public/
- 또는 `php artisan serve` 후 http://127.0.0.1:8000
- 관리자: `/admin`

## 체험 계정
| 구분 | 이메일 | 비밀번호 |
|---|---|---|
| 관리자 | admin@medisell.co.kr | medisell!2026 |
| 사업자(승인완료) | clinic@test.com | test1234 |
| 일반회원 | user@test.com | test1234 |

## 데이터베이스
- 이름: `medisell` (utf8mb4), 사용자 `root` / 비밀번호 없음(로컬 기본)
- 초기화: `php artisan migrate:fresh --seed`

## 핵심 컨셉 — 회원 구분 + 병원별 전용가
- 회원: **일반 회원 / 병원 회원**(`member_type` general/business)
- 가격 우선순위(`Product::priceFor($user)`):
  1. **병원별 전용가**(`hospital_prices`) — 병원 회원마다 제품별 계약가 (관리자가 매핑)
  2. **기본 병원가**(`products.member_price`) — 전용가 미설정 제품에 적용되는 기본값
  3. **정가**(`products.price`) — 일반회원·비로그인·미승인
- 병원 회원은 가입 시 `pending` → 관리자 회원관리에서 `approved` 처리 시 전용가 적용
- 로그인 시 자동으로 병원 전용가로 목록/상세/장바구니/주문 가격이 계산됨
- **병원별 가격 매핑은 관리자 → 회원관리 → 회원상세 페이지**에서 제품별로 등록/수정/삭제
  (`hospital_prices`: user_id + product_id 유니크, `App\Models\HospitalPrice`)

## 기능 범위 (1차 골격)
**프론트**
- 메인: 히어로 슬라이더 · 빠른카테고리 · 베스트/추천/신상품 · 브랜드 · 공지/입금계좌
- 카탈로그: 대분류→중분류 트리, 카테고리/검색/정렬, **브랜드·가격 필터**, 상품상세(갤러리·스펙·후기)
- **관심상품(위시리스트)**: 카드/상세 하트 토글, 헤더 카운트, 마이페이지 목록
- **최근 본 상품**: 세션 기반 추적, 우측 플로팅 퀵패널
- 장바구니 → 주문/결제(**토스페이먼츠 카드·가상계좌** / 무통장입금) → 주문완료 → 주문조회/취소
- **쿠폰(할인코드)**: 체크아웃에서 코드 적용(정액/정률·최소주문·기간·1인당/전체 한도), 취소 시 자동 롤백
  - **공개형**(코드입력 누구나) / **발행형**(발행받은 회원만) 지원
  - 관리자가 **특정 회원/회원구분(전체·병원·일반)에게 쿠폰 발행** → 회원 마이페이지 「쿠폰함」·체크아웃에 노출
- 회원: 일반/병원 가입(가입 적립금), 로그인, 마이페이지(주문·관심상품·적립금·정보수정)
- 커뮤니티: 공지사항, 상품후기, 견적·1:1문의
- **실시간 1:1 상담**: 모든 화면 우하단 플로팅 「문의하기」 채팅 위젯(회원·비회원), Pusher 실시간

**관리자**
- 대시보드(신규주문·승인대기·미답변문의·매출) + **기간별 매출 리포트**(일별 차트·인기상품 TOP10·상태분포)
- **CSV 내보내기**(주문·상품·회원, UTF-8 BOM 엑셀 호환)
- **적립금 수동조정**(회원별 가감·사유 기록)
- 주문관리: 상태변경, 입금확인 시 적립 자동지급, **송장(택배사·송장번호) 등록→배송중**,
  **주문취소 시 재고복구·적립금 정산(사용분 반환/적립분 회수)·토스 결제 자동환불**
- 회원관리(병원 승인·등급·병원전용가) + **병원 전용가 엑셀(CSV) 일괄 다운로드·업로드**(빈값=해제)
  + **회원 직접관리**(정보수정·비밀번호 초기화·관리자 지정/해제·강제탈퇴, 본인/마지막관리자 보호)
- 후기관리(노출/숨김 토글·삭제), 문의관리(답변), 실시간 상담 콘솔
- **사이트 설정**(회사정보·고객센터·무통장 계좌·배송비·적립률·인기검색어) — DB 저장,
  `AppServiceProvider`에서 `config('site')` 런타임 오버라이드 → 전 화면·로직 즉시 반영
  (`settings` 테이블 key-value JSON, `App\Models\Setting`)
- 쿠폰 관리(발행·기간·한도·사용현황) + **회원 대상 발행/회수**(전체·구분별·특정 이메일)
- 설정 기반 제네릭 CRUD: 카테고리·브랜드·상품·배너·공지·FAQ·쿠폰
  → `config/admin.php`에 블록 하나 추가하면 목록/등록/수정/삭제 자동 생성
  → 필드 타입 `image` 지원: **파일 업로드**(상품 대표이미지·배너·브랜드 로고), 미리보기·삭제,
    `storage/app/public/uploads/{리소스}`에 저장(웹접근 `public/storage` 심볼릭 링크)

## 주요 구조
- 모델: `app/Models/` (User, Category, Brand, Product, Banner, CartItem, Order, OrderItem, PointLog, Review, Inquiry, Notice, Faq)
- 컨트롤러: `app/Http/Controllers/` (Home, Catalog, Cart, Order, Mypage, Community, Auth) + `Admin/` (Dashboard, Resource, Order, User, Inquiry)
- 라우트: `routes/web.php`
- 뷰: `resources/views/` (layouts/app·admin, partials/header·footer·sidebar·mynav·icons, components/icon·product-card, 각 도메인 폴더)
- 테마: `public/css/site.css`(쇼핑몰), `public/css/admin.css`(관리자), `public/js/site.js`
- 전역 설정: `config/site.php`(회사정보·고객센터·입금계좌·배송/적립 정책)

## 데이터 (시드) — colscare 원본 import
카테고리·상품·이미지는 **`E:\xampp\htdocs\colscare`(DB: `cols`)** 원본에서 가져옵니다.
- `database/seeders/ColsImportSeeder.php` 가 `cols` DB(별도 연결, `config/database.php`)에서
  카테고리 트리·상품을 읽어 medisell 스키마로 매핑하고, 썸네일을 복사합니다.
- 이미지 3종 모두 가져옴(압축본 사용, `public/product`·`public/shop` ≈550MB):
  - **대표 썸네일** (`_01`) — 상품카드/목록
  - **갤러리 이미지** (`images` 배열, `_02` 등) — 상세페이지 썸네일 스트립(클릭 전환)
  - **상세설명 이미지** (`details` HTML 내 `_05` 대형 이미지 + 공통 배송안내 배너) — 137/142개
  - 원본: `colscare/storage/app/public/product/thumbnail/{경로}`(압축본 우선) → `public/product/{경로}`
  - `details` HTML의 `https://www.colscare.com/...` 이미지 URL은 로컬 경로로 자동 재작성
- 가져온 데이터: **5개 대분류 / 67개 카테고리 / 142개 상품 / 11개 브랜드(제품 라인)**
  (콜로플라스트 장루·요루·상처치료·카테터·스킨케어 실제 제품)
- 매핑 규칙: `unit_price`→정가, 정가×0.9→사업자 회원가, 가장 하위 카테고리에 연결, 재고 9999 상한
- 배너·공지·FAQ·후기·문의는 시더에서 별도 생성

> 재가져오기: `php artisan migrate:fresh --seed` (cols DB가 떠 있어야 함)

## 결제 — 토스페이먼츠 (결제위젯 v2)
- `.env`의 `TOSS_*` 키 사용(테스트키 `test_ck_`/`test_sk_`), `config/services.php` → `services.toss`
- 흐름: 체크아웃에서 결제수단 선택 → (토스) 주문 생성 후 `/order/pay/{order}` 결제위젯
  → `successUrl`(`payment.success`) → 서버 `confirm` API → 카드=즉시 결제완료 / 가상계좌=입금대기
  → 가상계좌 입금 시 토스 **웹훅**(`payment.webhook`)으로 자동 결제완료
- 구성요소: `App\Services\TossPayments`(confirm/조회), `PaymentController`(pay/success/fail/webhook),
  `Order::markPaid()`(결제완료+적립 1회), `orders`에 `payment_key·pay_status·pay_method·va_*` 컬럼
- 웹훅은 CSRF 제외(`bootstrap/app.php`), 금액 위변조 검증(success에서 amount 대조)
- **브라우저 수동 카드 테스트**: 로그인 → 상품 담기 → 결제하기 → 토스 결제창에서 테스트 카드로 결제
  (테스트 모드라 실제 청구 없음). 가상계좌 입금확인 웹훅은 운영 시 공개 URL 등록 필요(로컬은 관리자 수동 입금확인 가능)

## 실시간 1:1 상담 (Pusher Channels)
- `.env`의 `PUSHER_*`(app_id/key/secret/cluster=ap3), `BROADCAST_CONNECTION=pusher`, `pusher/pusher-php-server` 설치
- 공개 채널 방식(비회원도 사용) — 사설 채널 auth 불필요:
  - 방별 채널 `chat-{token}`(랜덤 토큰), 관리자 알림 채널 `chat-admin`
- 프론트: 모든 화면 우하단 플로팅 위젯(`partials/chat.blade.php`) — pusher-js CDN + fetch
  - 회원=user_id 기준 방, 비회원=세션 토큰 기준 방
- 관리자: `/admin/chat` 상담 콘솔(대화방 목록 + 실시간 대화 + 답장, 미확인 뱃지)
- 구성요소: `ChatRoom`·`ChatMessage` 모델, `ChatMessageSent`(ShouldBroadcast),
  `ChatController`(open/send), `Admin\ChatController`(index/show/reply)
- 채널/메시지 전송 모두 검증 완료(서버→Pusher trigger 200). 브라우저 간 실시간 수신은 pusher-js 표준 구독

## 전자세금계산서 (팝빌 Popbill)
- `linkhub/popbill` SDK, `config/popbill.php`(`.env`의 `POPBILL_*` 매핑)
- 관리자 주문상세 → 병원(사업자) 회원의 결제완료 주문에 **세금계산서 발행/취소/원본보기**
- 과세/면세: 상품 `tax_type` 기준 — 과세=세금계산서(부가세 10% 포함가에서 공급가·세액 분리), 면세=계산서(세액0)
- 구성: `PopbillTaxinvoiceService`(SDK 지연생성 래퍼), `TaxInvoiceIssueService`(주문→계산서),
  `TaxInvoice` 모델, `Admin\TaxInvoiceController`
- **⚠️ 시뮬레이트 안전장치**: `POPBILL_TAXINVOICE_SIMULATE` 기본 **true** → 실제 팝빌 호출 없이 발행이력만 생성.
  실발행하려면 `false`로 설정 + 공급자(`config/popbill.php`의 `supplier`)를 **메디셀 사업자로 등록**하고
  `POPBILL_IS_TEST`(팝빌 테스트/실환경) 확인 필요. 현재 `.env`는 leefriends(오다네트웍스) 계정 값이므로 그대로 실발행 금지.

## 무통장 입금 자동확인 (팝빌 계좌조회 EasyFinBank)
- 관리자 「입금확인」 → 기간 지정 수집 → **입금자명 + 금액** 일치 대기주문 자동 결제확인(`Order::markPaid`)
- 미매칭 입금건은 수동 매칭 UI 제공
- 구성: `PopbillEasyFinBankService`(지연생성), `BankDepositService`(수집·매칭),
  `BankDeposit`·`BankCollectJob` 모델, `Admin\BankDepositController`
- **⚠️ 시뮬레이트 안전장치**: `POPBILL_BANK_SIMULATE` 기본 **true** → 실계좌 조회 대신 무통장 대기주문 기반
  가상 입금건 생성으로 매칭 흐름 검증. 실연동은 `false` + 조회할 계좌를 팝빌 계좌조회에 등록 + `POPBILL_BANK_ACCOUNT`/`POPBILL_BANK_CODE` 설정 필요.

## 쿠팡 경쟁가 조회 (타사 판매정보)
- 관리자 「쿠팡 경쟁가」 → 메디셀 제품 선택(또는 키워드) → 쿠팡 검색결과(타 판매자·가격) 목록
- 최저/평균/최고가 + **메디셀 판매가 대비 차이**·경쟁력 코멘트
- 구성: `config/coupang.php`, `CoupangSearchService`(검색), `Admin\CoupangController`
- **⚠️ 시뮬레이트 안전장치**: `COUPANG_SIMULATE` 기본 **true** → 제품명 기반 결정적 모의 경쟁가 생성.
  실연동은 쿠팡 공개 상품검색 API 부재로 **파트너스(제휴) 검색 API** 또는 **검색 크롤링**이 필요(승인·차단·약관 제약).
  제품코드(SKU)로는 타사 리스팅 매칭 불가 → **제품명 검색** 기반.

## 향후 확장 포인트
- 등급별 차등할인, 상품 일괄(품절·노출) 관리, 재고 입고관리,
  배송추적 외부연동, 정기배송, 세금계산서 발행 등
