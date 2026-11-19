<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Enums\CalendarEventRecurrence;
use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarMissingDayPolicy;
use App\Enums\CalendarSystem;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Schema;

/**
 * Fixed Registry of Buddhist, Folk, Cultural, National and Astronomical Calendar Events.
 *
 * All dates, rules, and event keys are immutable and hardcoded in source code.
 * Database only stores user-facing descriptions / significance edited by admin.
 */
class FixedCalendarEventRegistry
{
    /**
     * Complete list of all 48 fixed events defined in source code.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static ?array $events = null;

    /**
     * Get all fixed event definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        if (self::$events === null) {
            self::$events = self::buildEventDefinitions();
        }

        return self::$events;
    }

    /**
     * Get single fixed event by key.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        $all = self::all();

        return $all[$key] ?? null;
    }

    /**
     * Check if an event key exists in the fixed registry.
     */
    public static function has(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    /**
     * Get count of fixed events.
     */
    public static function count(): int
    {
        return count(self::all());
    }

    /**
     * Sync all fixed events to the `calendar_events` database table.
     * Preserves any existing event descriptions edited by admin.
     *
     * @return int Number of events synced
     */
    public static function syncToDatabase(): int
    {
        if (! Schema::hasTable('calendar_events')) {
            return 0;
        }

        $events = self::all();
        $syncedCount = 0;

        foreach ($events as $key => $def) {
            $existing = CalendarEvent::where('slug', $key)->first();

            $data = [
                'title' => $def['title'],
                'event_type' => $def['event_type']->value,
                'calendar_system' => $def['calendar_system']->value,
                'recurrence_type' => $def['recurrence_type']->value,
                'solar_month' => $def['solar_month'] ?? null,
                'solar_day' => $def['solar_day'] ?? null,
                'lunar_month' => $def['lunar_month'] ?? null,
                'lunar_day' => $def['lunar_day'] ?? null,
                'missing_day_policy' => ($def['missing_day_policy'] ?? CalendarMissingDayPolicy::Skip)->value,
                'is_general' => true,
                'sort_order' => $def['sort_order'] ?? 0,
                'status' => CalendarEventStatus::Published->value,
            ];

            if ($existing) {
                // Update technical fields and title if changed in code, KEEP admin description!
                $existing->update($data);
            } else {
                // First-time insert: default description if provided
                $data['slug'] = $key;
                $data['description'] = $def['default_description'] ?? null;
                $data['short_description'] = $def['default_short_description'] ?? null;
                $data['published_at'] = now();
                CalendarEvent::create($data);
            }

            $syncedCount++;
        }

        return $syncedCount;
    }

    /**
     * Build the hardcoded definition of all 48 fixed events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function buildEventDefinitions(): array
    {
        return [
            // ==========================================
            // 01. GREGORIAN SOLAR EVENTS (6 events)
            // ==========================================
            'tet_duong_lich' => [
                'key' => 'tet_duong_lich',
                'title' => 'Tết Dương Lịch',
                'event_type' => CalendarEventType::NationalHoliday,
                'calendar_system' => CalendarSystem::Solar,
                'recurrence_type' => CalendarEventRecurrence::AnnualSolar,
                'solar_month' => 1,
                'solar_day' => 1,
                'date_rule' => 'gregorian_fixed',
                'sort_order' => 1,
                'rule_summary' => 'Hằng năm vào ngày 01/01 Dương lịch',
                'default_short_description' => 'Ngày đầu tiên của năm mới theo Dương lịch quốc tế.',
            ],
            'quoc_te_phu_nu' => [
                'key' => 'quoc_te_phu_nu',
                'title' => 'Ngày Quốc tế Phụ Nữ',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Solar,
                'recurrence_type' => CalendarEventRecurrence::AnnualSolar,
                'solar_month' => 3,
                'solar_day' => 8,
                'date_rule' => 'gregorian_fixed',
                'sort_order' => 2,
                'rule_summary' => 'Hằng năm vào ngày 08/03 Dương lịch',
                'default_short_description' => 'Ngày kỷ niệm và tôn vinh phụ nữ trên toàn thế giới.',
            ],
            'giai_phong_mien_nam' => [
                'key' => 'giai_phong_mien_nam',
                'title' => 'Giải phóng Miền Nam - Thống nhất đất nước',
                'event_type' => CalendarEventType::NationalHoliday,
                'calendar_system' => CalendarSystem::Solar,
                'recurrence_type' => CalendarEventRecurrence::AnnualSolar,
                'solar_month' => 4,
                'solar_day' => 30,
                'date_rule' => 'gregorian_fixed',
                'sort_order' => 3,
                'rule_summary' => 'Hằng năm vào ngày 30/04 Dương lịch',
                'default_short_description' => 'Ngày Giải phóng hoàn toàn miền Nam, thống nhất non sông đất nước Việt Nam.',
            ],
            'quoc_te_lao_dong' => [
                'key' => 'quoc_te_lao_dong',
                'title' => 'Ngày Quốc tế Lao động',
                'event_type' => CalendarEventType::NationalHoliday,
                'calendar_system' => CalendarSystem::Solar,
                'recurrence_type' => CalendarEventRecurrence::AnnualSolar,
                'solar_month' => 5,
                'solar_day' => 1,
                'date_rule' => 'gregorian_fixed',
                'sort_order' => 4,
                'rule_summary' => 'Hằng năm vào ngày 01/05 Dương lịch',
                'default_short_description' => 'Ngày kỷ niệm của giai cấp công nhân và người lao động toàn cầu.',
            ],
            'quoc_te_thieu_nhi' => [
                'key' => 'quoc_te_thieu_nhi',
                'title' => 'Ngày Quốc tế Thiếu nhi',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Solar,
                'recurrence_type' => CalendarEventRecurrence::AnnualSolar,
                'solar_month' => 6,
                'solar_day' => 1,
                'date_rule' => 'gregorian_fixed',
                'sort_order' => 5,
                'rule_summary' => 'Hằng năm vào ngày 01/06 Dương lịch',
                'default_short_description' => 'Ngày lễ dành riêng cho trẻ em, nhắc nhở bảo vệ và chăm sóc thế hệ mầm non.',
            ],
            'quoc_khanh' => [
                'key' => 'quoc_khanh',
                'title' => 'Ngày Quốc khánh',
                'event_type' => CalendarEventType::NationalHoliday,
                'calendar_system' => CalendarSystem::Solar,
                'recurrence_type' => CalendarEventRecurrence::AnnualSolar,
                'solar_month' => 9,
                'solar_day' => 2,
                'date_rule' => 'gregorian_fixed',
                'sort_order' => 6,
                'rule_summary' => 'Hằng năm vào ngày 02/09 Dương lịch',
                'default_short_description' => 'Ngày Chủ tịch Hồ Chí Minh đọc Tuyên ngôn Độc lập khai sinh nước Việt Nam Dân chủ Cộng hòa.',
            ],

            // ==========================================
            // 02. TẾT NGUYÊN ĐÁN (01/01 Âm lịch)
            // ==========================================
            'tet_nguyen_dan' => [
                'key' => 'tet_nguyen_dan',
                'title' => 'Tết Nguyên Đán',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 1,
                'lunar_day' => 1,
                'date_rule' => 'lunar_tet_nguyen_dan',
                'sort_order' => 10,
                'rule_summary' => 'Hằng năm vào ngày Mùng 1 Tết (01/01 Âm lịch), tự quy đổi theo từng năm',
                'default_short_description' => 'Tết cổ truyền của dân tộc Việt Nam, thời khắc khởi đầu năm mới âm lịch.',
            ],

            // ==========================================
            // 03. SOLAR TERM: LẬP XUÂN
            // ==========================================
            'ngay_lap_xuan' => [
                'key' => 'ngay_lap_xuan',
                'title' => 'Ngày Lập Xuân',
                'event_type' => CalendarEventType::SolarTerm,
                'calendar_system' => CalendarSystem::SolarTerm,
                'recurrence_type' => CalendarEventRecurrence::SolarTerm,
                'date_rule' => 'solar_term_lap_xuan',
                'sort_order' => 15,
                'rule_summary' => 'Thời điểm bắt đầu tiết Lập Xuân theo kinh độ Mặt Trời (315°), tính theo múi giờ Việt Nam',
                'default_short_description' => 'Tiết khí đầu tiên trong 24 tiết khí, đánh dấu sự chuyển mình bắt đầu của mùa xuân.',
            ],

            // ==========================================
            // 04. FIXED LUNAR EVENTS (Section 06)
            // ==========================================
            // Tháng 12 Âm
            'via_phat_thich_ca_thanh_dao' => [
                'key' => 'via_phat_thich_ca_thanh_dao',
                'title' => 'Ngày Vía Phật Thích Ca Thành Đạo',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 12,
                'lunar_day' => 8,
                'date_rule' => 'lunar_annual',
                'sort_order' => 20,
                'rule_summary' => 'Hằng năm vào ngày 08/12 Âm lịch',
                'default_short_description' => 'Kỷ niệm Đức Thích Ca Mâu Ni đại triệt đại ngộ dưới cội bồ đề thành tựu quả vị Chánh đẳng Chánh giác.',
            ],
            'tien_ong_tao_ve_troi' => [
                'key' => 'tien_ong_tao_ve_troi',
                'title' => 'Ngày Tiễn Ông Công Ông Táo Về Trời',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 12,
                'lunar_day' => 23,
                'date_rule' => 'lunar_annual',
                'sort_order' => 21,
                'rule_summary' => 'Hằng năm vào ngày 23/12 Âm lịch',
                'default_short_description' => 'Phong tục truyền thống dân gian tiễn Táo Quân chầu trời bái tạ Ngọc Hoàng cuối năm.',
            ],

            // Tháng 01 Âm
            'via_phat_di_lac' => [
                'key' => 'via_phat_di_lac',
                'title' => 'Vía Phật Di Lặc',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 1,
                'lunar_day' => 1,
                'date_rule' => 'lunar_annual',
                'sort_order' => 22,
                'rule_summary' => 'Hằng năm vào ngày 01/01 Âm lịch',
                'default_short_description' => 'Ngày vía Đức Di Lặc Tôn Phật, biểu trưng cho niềm hoan hỷ, từ bi và an lạc đầu năm.',
            ],
            'via_than_tai' => [
                'key' => 'via_than_tai',
                'title' => 'Vía Thần Tài',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 1,
                'lunar_day' => 10,
                'date_rule' => 'lunar_annual',
                'sort_order' => 23,
                'rule_summary' => 'Hằng năm vào ngày 10/01 Âm lịch',
                'default_short_description' => 'Tập tục tín ngưỡng dân gian cầu may mắn, hanh thông về tài lộc và kinh doanh đầu năm.',
            ],
            'dai_hoi_thanh_tang_ram_thang_gieng' => [
                'key' => 'dai_hoi_thanh_tang_ram_thang_gieng',
                'title' => 'Ngày Đại hội Thánh Tăng - Rằm Tháng Giêng',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 1,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 24,
                'rule_summary' => 'Hằng năm vào ngày 15/01 Âm lịch (Rằm Thượng Nguyên)',
                'default_short_description' => 'Kỷ niệm Đại hội Thánh Tăng và Tết Nguyên Tiêu thanh tịnh đầu năm.',
            ],

            // Tháng 02 Âm
            'via_phat_thich_ca_xuat_gia' => [
                'key' => 'via_phat_thich_ca_xuat_gia',
                'title' => 'Ngày Phật Thích Ca Mâu Ni Xuất Gia',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 2,
                'lunar_day' => 8,
                'date_rule' => 'lunar_annual',
                'sort_order' => 25,
                'rule_summary' => 'Hằng năm vào ngày 08/02 Âm lịch',
                'default_short_description' => 'Kỷ niệm Thái tử Tất Đạt Đa từ bỏ cung vàng điện ngọc, vượt thành xuất gia tìm cầu chân lý giải thoát.',
            ],
            'via_phat_thich_ca_nhap_niet_ban' => [
                'key' => 'via_phat_thich_ca_nhap_niet_ban',
                'title' => 'Ngày Phật Thích Ca Mâu Ni Nhập Niết Bàn',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 2,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 26,
                'rule_summary' => 'Hằng năm vào ngày 15/02 Âm lịch',
                'default_short_description' => 'Kỷ niệm Đức Thế Tôn thị hiện nhập Đại Bát Niết Bàn tại rừng Sa La Song Thọ xứ Câu Thi Na.',
            ],
            'via_quan_the_am_dan_sanh' => [
                'key' => 'via_quan_the_am_dan_sanh',
                'title' => 'Ngày Vía Quán Thế Âm Bồ Tát Đản Sanh',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 2,
                'lunar_day' => 19,
                'date_rule' => 'lunar_annual',
                'sort_order' => 27,
                'rule_summary' => 'Hằng năm vào ngày 19/02 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày Đản sinh của Đức Đại Bi Quán Thế Âm Bồ Tát cứu khổ cứu nạn.',
            ],
            'via_pho_hien_dan_sanh' => [
                'key' => 'via_pho_hien_dan_sanh',
                'title' => 'Ngày Vía Phổ Hiền Bồ Tát Đản Sanh',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 2,
                'lunar_day' => 21,
                'date_rule' => 'lunar_annual',
                'sort_order' => 28,
                'rule_summary' => 'Hằng năm vào ngày 21/02 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày Đản sinh của Đức Đại Hạnh Phổ Hiền Bồ Tát, bậc hộ trì Mười Đại Nguyện.',
            ],

            // Tháng 03 Âm
            'tet_han_thuc' => [
                'key' => 'tet_han_thuc',
                'title' => 'Tết Hàn Thực',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 3,
                'lunar_day' => 3,
                'date_rule' => 'lunar_annual',
                'sort_order' => 29,
                'rule_summary' => 'Hằng năm vào ngày 03/03 Âm lịch',
                'default_short_description' => 'Ngày tết truyền thống bánh trôi, bánh chay hướng về nguồn cội gia đình tổ tiên.',
            ],
            'via_ton_gia_ma_ha_ca_diep' => [
                'key' => 'via_ton_gia_ma_ha_ca_diep',
                'title' => 'Ngày Vía Tôn giả Ma Ha Ca Diếp',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 3,
                'lunar_day' => 6,
                'date_rule' => 'lunar_annual',
                'sort_order' => 30,
                'rule_summary' => 'Hằng năm vào ngày 06/03 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Tôn giả Đại Ca Diếp, Đệ nhất Đầu đà, Sơ tổ Thiền tông xứ Ấn.',
            ],
            'gio_to_hung_vuong' => [
                'key' => 'gio_to_hung_vuong',
                'title' => 'Giỗ Tổ Hùng Vương',
                'event_type' => CalendarEventType::NationalHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 3,
                'lunar_day' => 10,
                'date_rule' => 'lunar_annual',
                'sort_order' => 31,
                'rule_summary' => 'Hằng năm vào ngày 10/03 Âm lịch',
                'default_short_description' => 'Ngày Quốc lễ tri ân công đức dựng nước của các Vua Hùng.',
            ],
            'via_duc_dai_mau_chuan_de' => [
                'key' => 'via_duc_dai_mau_chuan_de',
                'title' => 'Vía Đức Đại Mẫu Chuẩn Đề Bồ Tát',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 3,
                'lunar_day' => 16,
                'date_rule' => 'lunar_annual',
                'sort_order' => 32,
                'rule_summary' => 'Hằng năm vào ngày 16/03 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Thất Câu Chi Phật Mẫu Chuẩn Đề Bồ Tát.',
            ],

            // Tháng 04 Âm
            'mung_thang_phat_dan' => [
                'key' => 'mung_thang_phat_dan',
                'title' => 'Mừng Tháng Phật Đản',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 4,
                'lunar_day' => 1,
                'date_rule' => 'lunar_annual',
                'sort_order' => 33,
                'rule_summary' => 'Hằng năm vào ngày 01/04 Âm lịch',
                'default_short_description' => 'Khởi đầu mùa Phật đản thiêng liêng trong tinh thần tri ân và hoan hỷ của người con Phật.',
            ],
            'via_dai_tri_van_thu_su_loi' => [
                'key' => 'via_dai_tri_van_thu_su_loi',
                'title' => 'Vía Đại Trí Văn Thù Sư Lợi Bồ Tát',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 4,
                'lunar_day' => 4,
                'date_rule' => 'lunar_annual',
                'sort_order' => 34,
                'rule_summary' => 'Hằng năm vào ngày 04/04 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Văn Thù Sư Lợi Bồ Tát, hiện thân của trí tuệ vô thượng.',
            ],
            'dai_le_phat_dan' => [
                'key' => 'dai_le_phat_dan',
                'title' => 'Đại Lễ Phật Đản',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 4,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 35,
                'rule_summary' => 'Hằng năm vào ngày 15/04 Âm lịch (Đại lễ Vesak)',
                'default_short_description' => 'Đại lễ thiêng liêng kỷ niệm ngày Đản sinh của Đức Bổn Sư Thích Ca Mâu Ni Phật.',
            ],
            'ngay_an_cu_kiet_ha' => [
                'key' => 'ngay_an_cu_kiet_ha',
                'title' => 'Ngày An Cư Kiết Hạ',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 4,
                'lunar_day' => 16,
                'date_rule' => 'lunar_annual',
                'sort_order' => 36,
                'rule_summary' => 'Hằng năm vào ngày 16/04 Âm lịch',
                'default_short_description' => 'Ngày chư Tăng Ni bắt đầu thời kỳ cấm túc tu học thúc liễm thân tâm trong ba tháng mùa hạ.',
            ],
            'via_quang_duc_bo_tat' => [
                'key' => 'via_quang_duc_bo_tat',
                'title' => 'Vía Quảng Đức Bồ Tát Vị Pháp Thiêu Thân',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 4,
                'lunar_day' => 20,
                'date_rule' => 'lunar_annual',
                'sort_order' => 37,
                'rule_summary' => 'Hằng năm vào ngày 20/04 Âm lịch',
                'default_short_description' => 'Tưởng niệm Bồ Tát Thích Quảng Đức tự thiêu thân để bảo vệ Đạo pháp và tự do tín ngưỡng năm 1963.',
            ],
            'via_pho_hien_thanh_dao' => [
                'key' => 'via_pho_hien_thanh_dao',
                'title' => 'Vía Phổ Hiền Bồ Tát Thành Đạo',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 4,
                'lunar_day' => 23,
                'date_rule' => 'lunar_annual',
                'sort_order' => 38,
                'rule_summary' => 'Hằng năm vào ngày 23/04 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Phổ Hiền Bồ Tát thành đạo.',
            ],
            'via_duoc_su_thang_4' => [
                'key' => 'via_duoc_su_thang_4',
                'title' => 'Vía Dược Sư Lưu Ly Quang Vương Phật',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 4,
                'lunar_day' => 28,
                'date_rule' => 'lunar_annual',
                'sort_order' => 39,
                'rule_summary' => 'Hằng năm vào ngày 28/04 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Dược Sư Như Lai, ban trải tâm từ tiêu trừ tật bệnh và khổ nạn.',
            ],

            // Tháng 05 Âm
            'tet_doan_ngo' => [
                'key' => 'tet_doan_ngo',
                'title' => 'Tết Đoan Ngọ',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 5,
                'lunar_day' => 5,
                'date_rule' => 'lunar_annual',
                'sort_order' => 40,
                'rule_summary' => 'Hằng năm vào ngày 05/05 Âm lịch',
                'default_short_description' => 'Tết cổ truyền mùng 5 tháng 5 theo văn hóa dân gian Việt Nam.',
            ],
            'via_gia_lam_thanh_chung' => [
                'key' => 'via_gia_lam_thanh_chung',
                'title' => 'Vía Già Lam Thánh Chúng',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 5,
                'lunar_day' => 13,
                'date_rule' => 'lunar_annual',
                'sort_order' => 41,
                'rule_summary' => 'Hằng năm vào ngày 13/05 Âm lịch',
                'default_short_description' => 'Kỷ niệm chư vị Hộ pháp Già Lam Thánh Chúng hộ trì chốn thiền môn thanh tịnh.',
            ],

            // Tháng 06 Âm
            'via_vi_da_ho_phap' => [
                'key' => 'via_vi_da_ho_phap',
                'title' => 'Vía Vi Đà Hộ Pháp',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 6,
                'lunar_day' => 3,
                'date_rule' => 'lunar_annual',
                'sort_order' => 42,
                'rule_summary' => 'Hằng năm vào ngày 03/06 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Hộ Pháp Vi Đà Tôn Thiên Bồ Tát hộ trì chánh pháp.',
            ],
            'ngay_duc_phat_chuyen_phap_luan' => [
                'key' => 'ngay_duc_phat_chuyen_phap_luan',
                'title' => 'Ngày Đức Phật Chuyển Pháp Luân',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 6,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 43,
                'rule_summary' => 'Hằng năm vào ngày 15/06 Âm lịch (Đại lễ Asalha Puja)',
                'default_short_description' => 'Kỷ niệm Đức Thế Tôn thuyết bài kinh Chuyển Pháp Luân đầu tiên tại Vườn Lộc Uyển khai sáng Tam Bảo.',
            ],
            'via_quan_the_am_thanh_dao' => [
                'key' => 'via_quan_the_am_thanh_dao',
                'title' => 'Vía Quán Thế Âm Bồ Tát Thành Đạo',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 6,
                'lunar_day' => 19,
                'date_rule' => 'lunar_annual',
                'sort_order' => 44,
                'rule_summary' => 'Hằng năm vào ngày 19/06 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày Đức Bồ Tát Quán Thế Âm thành tựu quả vị viên thông cứu độ chúng sinh.',
            ],

            // Tháng 07 Âm
            'mung_thang_vu_lan' => [
                'key' => 'mung_thang_vu_lan',
                'title' => 'Mừng Tháng Vu Lan',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 7,
                'lunar_day' => 1,
                'date_rule' => 'lunar_annual',
                'sort_order' => 45,
                'rule_summary' => 'Hằng năm vào ngày 01/07 Âm lịch',
                'default_short_description' => 'Khởi đầu mùa Vu Lan Báo Hiếu, mùa tri ân sâu nặng tứ trọng ân.',
            ],
            'via_dai_the_chi_bo_tat' => [
                'key' => 'via_dai_the_chi_bo_tat',
                'title' => 'Vía Đại Thế Chí Bồ Tát',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 7,
                'lunar_day' => 13,
                'date_rule' => 'lunar_annual',
                'sort_order' => 46,
                'rule_summary' => 'Hằng năm vào ngày 13/07 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Đại Thế Chí Bồ Tát dùng ánh sáng trí tuệ soi chiếu muôn cõi.',
            ],
            'dai_le_vu_lan' => [
                'key' => 'dai_le_vu_lan',
                'title' => 'Đại Lễ Vu Lan',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 7,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 47,
                'rule_summary' => 'Hằng năm vào ngày 15/07 Âm lịch (Vu Lan Báo Hiếu & Tự Tứ)',
                'default_short_description' => 'Đại lễ Vu Lan Báo Hiếu báo đáp công ơn cha mẹ và ngày chư Phật hoan hỷ.',
            ],
            'via_dia_tang_vuong_bo_tat' => [
                'key' => 'via_dia_tang_vuong_bo_tat',
                'title' => 'Vía Địa Tạng Vương Bồ Tát',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 7,
                'lunar_day' => 30,
                'missing_day_policy' => CalendarMissingDayPolicy::LastDay,
                'date_rule' => 'last_day_of_lunar_month',
                'sort_order' => 48,
                'rule_summary' => 'Hằng năm vào ngày cuối tháng 7 Âm lịch (Ngày 30 nếu tháng đủ, ngày 29 nếu tháng thiếu)',
                'default_short_description' => 'Kỷ niệm ngày vía Đức U Minh Giáo Chủ Địa Tạng Vương Bồ Tát đại bi đại nguyện cứu bạt u đồ.',
            ],

            // Tháng 08 Âm
            'via_ton_gia_a_nan_da' => [
                'key' => 'via_ton_gia_a_nan_da',
                'title' => 'Vía Tôn giả A Nan Đà',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 8,
                'lunar_day' => 8,
                'date_rule' => 'lunar_annual',
                'sort_order' => 49,
                'rule_summary' => 'Hằng năm vào ngày 08/08 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Tôn giả A Nan, Đệ nhất Đa văn thị giả của Đức Phật.',
            ],
            'ram_trung_thu' => [
                'key' => 'ram_trung_thu',
                'title' => 'Rằm Trung Thu',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 8,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 50,
                'rule_summary' => 'Hằng năm vào ngày 15/08 Âm lịch (Tết Trung Thu)',
                'default_short_description' => 'Tết trông trăng, ngày hội sum họp gia đình và vui tết thiếu nhi truyền thống.',
            ],

            // Tháng 09 Âm
            'tet_trung_cuu' => [
                'key' => 'tet_trung_cuu',
                'title' => 'Tết Trùng Cửu',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 9,
                'lunar_day' => 9,
                'date_rule' => 'lunar_annual',
                'sort_order' => 51,
                'rule_summary' => 'Hằng năm vào ngày 09/09 Âm lịch',
                'default_short_description' => 'Ngày tết Trùng Dương theo phong tục truyền thống Á Đông.',
            ],
            'ngay_tu_tu' => [
                'key' => 'ngay_tu_tu',
                'title' => 'Ngày Tự Tứ',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 9,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 52,
                'rule_summary' => 'Hằng năm vào ngày 15/09 Âm lịch (Tự Tứ / Mãn Hạ Nam truyền)',
                'default_short_description' => 'Lễ Tự tứ đánh dấu kết thúc mùa an cư kiết hạ theo truyền thống Phật giáo.',
            ],
            'via_quan_the_am_xuat_gia' => [
                'key' => 'via_quan_the_am_xuat_gia',
                'title' => 'Vía Quán Thế Âm Bồ Tát Xuất Gia',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 9,
                'lunar_day' => 19,
                'date_rule' => 'lunar_annual',
                'sort_order' => 53,
                'rule_summary' => 'Hằng năm vào ngày 19/09 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày Đức Bồ Tát Quán Thế Âm xuất gia xả ly trần tục.',
            ],
            'via_duoc_su_thang_9' => [
                'key' => 'via_duoc_su_thang_9',
                'title' => 'Vía Dược Sư Lưu Ly Quang Vương Phật',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 9,
                'lunar_day' => 30,
                'missing_day_policy' => CalendarMissingDayPolicy::LastDay,
                'date_rule' => 'last_day_of_lunar_month',
                'sort_order' => 54,
                'rule_summary' => 'Hằng năm vào ngày cuối tháng 9 Âm lịch (Ngày 30 nếu tháng đủ, ngày 29 nếu tháng thiếu)',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Dược Sư Lưu Ly Quang Như Lai tiêu tai diên thọ.',
            ],

            // Tháng 10 Âm
            'via_bo_de_dat_ma' => [
                'key' => 'via_bo_de_dat_ma',
                'title' => 'Vía Bồ Đề Đạt Ma',
                'event_type' => CalendarEventType::BuddhaBodhisattvaObservance,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 10,
                'lunar_day' => 5,
                'date_rule' => 'lunar_annual',
                'sort_order' => 55,
                'rule_summary' => 'Hằng năm vào ngày 05/10 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày Tổ sư Bồ Đề Đạt Ma, vị Sơ Tổ của Thiền tông Đông Độ.',
            ],
            'tet_thuong_tan' => [
                'key' => 'tet_thuong_tan',
                'title' => 'Tết Thường Tân',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 10,
                'lunar_day' => 10,
                'date_rule' => 'lunar_annual',
                'sort_order' => 56,
                'rule_summary' => 'Hằng năm vào ngày 10/10 Âm lịch',
                'default_short_description' => 'Tết cơm mới mừng mùa màng ấm no, tạ ơn trời đất tổ tiên theo phong tục Việt Nam.',
            ],
            'le_ha_nguyen' => [
                'key' => 'le_ha_nguyen',
                'title' => 'Lễ Hạ Nguyên',
                'event_type' => CalendarEventType::CulturalFestival,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 10,
                'lunar_day' => 15,
                'date_rule' => 'lunar_annual',
                'sort_order' => 57,
                'rule_summary' => 'Hằng năm vào ngày 15/10 Âm lịch (Rằm Tháng Mười)',
                'default_short_description' => 'Lễ Hạ Nguyên tạ ơn ngũ cốc và kính nhớ cội nguồn trong ba kỳ rằm lớn của năm.',
            ],

            // Tháng 11 Âm
            'via_duc_phat_a_di_da' => [
                'key' => 'via_duc_phat_a_di_da',
                'title' => 'Vía Đức Phật A Di Đà',
                'event_type' => CalendarEventType::BuddhistHoliday,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
                'lunar_month' => 11,
                'lunar_day' => 17,
                'date_rule' => 'lunar_annual',
                'sort_order' => 58,
                'rule_summary' => 'Hằng năm vào ngày 17/11 Âm lịch',
                'default_short_description' => 'Kỷ niệm ngày vía Đức Phật A Di Đà, Giáo chủ cõi Tây Phương Cực Lạc.',
            ],

            // ==========================================
            // 05. MONTHLY THẬP TRAI RULE
            // ==========================================
            'thap_trai' => [
                'key' => 'thap_trai',
                'title' => 'Ngày Thập Trai',
                'event_type' => CalendarEventType::VegetarianDay,
                'calendar_system' => CalendarSystem::Lunar,
                'recurrence_type' => CalendarEventRecurrence::MonthlyLunar,
                'date_rule' => 'thap_trai_monthly',
                'sort_order' => 99,
                'rule_summary' => '10 ngày trai giới mỗi tháng ÂL (Tháng đủ: 1,8,14,15,18,23,24,28,29,30; Tháng thiếu: 1,8,14,15,18,23,24,27,28,29)',
                'default_short_description' => 'Ngày ăn chay, trì giới và tu tập tâm từ bi theo truyền thống Thập Trai.',
            ],
        ];
    }
}
