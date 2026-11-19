<?php

namespace App\Services\Practice;

use App\Enums\PracticeType;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\MantraVersion;
use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class PracticeTypeRegistry
{
    /**
     * Whitelist mapping of public subject aliases to Eloquent model classes.
     *
     * @var array<string, class-string<Model>>
     */
    public const SUBJECT_MAP = [
        'sutra' => Sutra::class,
        'sutra_version' => SutraVersion::class,
        'sutra_section' => SutraSection::class,
        'mantra' => Mantra::class,
        'mantra_version' => MantraVersion::class,
        'buddhist_figure' => BuddhistFigure::class,
    ];

    /**
     * Resolve model class from public subject type alias.
     */
    public function resolveSubjectModelClass(string $typeAlias): ?string
    {
        return self::SUBJECT_MAP[$typeAlias] ?? null;
    }

    /**
     * Resolve public subject type alias from model class.
     */
    public function resolveSubjectTypeAlias(string|object $modelOrClass): ?string
    {
        $class = is_object($modelOrClass) ? get_class($modelOrClass) : $modelOrClass;
        $flipped = array_flip(self::SUBJECT_MAP);

        return $flipped[$class] ?? null;
    }

    /**
     * Validate and resolve the referenced Buddhist content subject.
     *
     * @throws ValidationException
     */
    public function validateAndResolveSubject(
        PracticeType $practiceType,
        ?string $subjectType,
        ?int $subjectId
    ): ?Model {
        if (! $subjectType && ! $subjectId) {
            if ($practiceType->isSubjectRequired()) {
                throw ValidationException::withMessages([
                    'subject_type' => "Hình thức tu tập '{$practiceType->label()}' yêu cầu liên kết nội dung Phật giáo cụ thể.",
                ]);
            }

            return null;
        }

        if (! $subjectType || ! $subjectId) {
            throw ValidationException::withMessages([
                'subject_id' => 'Vui lòng cung cấp đầy đủ cả subject_type và subject_id khi liên kết nội dung.',
            ]);
        }

        $allowedTypes = $practiceType->allowedSubjectTypes();
        if (! in_array($subjectType, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'subject_type' => "Loại nội dung '{$subjectType}' không được phép cho hình thức '{$practiceType->label()}'.",
            ]);
        }

        $modelClass = $this->resolveSubjectModelClass($subjectType);
        if (! $modelClass) {
            throw ValidationException::withMessages([
                'subject_type' => "Loại nội dung '{$subjectType}' không hợp lệ.",
            ]);
        }

        /** @var Model|null $model */
        $model = $modelClass::find($subjectId);
        if (! $model) {
            throw ValidationException::withMessages([
                'subject_id' => 'Nội dung Phật giáo được liên kết không tồn tại.',
            ]);
        }

        if (! $this->isSubjectPublished($model)) {
            throw ValidationException::withMessages([
                'subject_id' => 'Không thể tạo phiên tu tập trên nội dung chưa được xuất bản công khai.',
            ]);
        }

        return $model;
    }

    /**
     * Check if a subject model hierarchy is strictly published.
     */
    public function isSubjectPublished(Model $model): bool
    {
        if ($model instanceof Sutra) {
            return $model->isPublished();
        }

        if ($model instanceof SutraVersion) {
            return $model->isPublished() && ($model->sutra && $model->sutra->isPublished());
        }

        if ($model instanceof SutraSection) {
            $version = $model->version;

            return $version && $version->isPublished() && ($version->sutra && $version->sutra->isPublished());
        }

        if ($model instanceof Mantra) {
            return $model->isPublished();
        }

        if ($model instanceof MantraVersion) {
            return $model->isPublished() && ($model->mantra && $model->mantra->isPublished());
        }

        if ($model instanceof BuddhistFigure) {
            return $model->isPublished();
        }

        return false;
    }

    /**
     * Validate and sanitize progress_data strictly according to practice type schema.
     *
     * @param  array<string, mixed>|null  $progressData
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateAndSanitizeProgressData(PracticeType $practiceType, ?array $progressData): array
    {
        if ($progressData === null || empty($progressData)) {
            return [];
        }

        $sanitized = [];

        switch ($practiceType) {
            case PracticeType::Meditation:
                if (isset($progressData['planned_duration_seconds'])) {
                    $val = $progressData['planned_duration_seconds'];
                    if (! is_numeric($val) || (int) $val < 60 || (int) $val > 86400) {
                        throw ValidationException::withMessages([
                            'progress_data.planned_duration_seconds' => 'Thời gian thiền dự kiến phải từ 60 giây đến 86.400 giây (24 giờ).',
                        ]);
                    }
                    $sanitized['planned_duration_seconds'] = (int) $val;
                }
                break;

            case PracticeType::Mala:
                if (isset($progressData['bead_count'])) {
                    $val = $progressData['bead_count'];
                    if (! is_numeric($val) || (int) $val < 0 || (int) $val > 100000) {
                        throw ValidationException::withMessages([
                            'progress_data.bead_count' => 'Số lượng hạt tràng phải là số nguyên không âm (tối đa 100.000).',
                        ]);
                    }
                    $sanitized['bead_count'] = (int) $val;
                }
                if (isset($progressData['target_beads'])) {
                    $val = $progressData['target_beads'];
                    if (is_numeric($val) && (int) $val > 0) {
                        $sanitized['target_beads'] = (int) $val;
                    }
                }
                break;

            case PracticeType::MantraRecitation:
            case PracticeType::BuddhaNameRecitation:
                if (isset($progressData['repetition_count'])) {
                    $val = $progressData['repetition_count'];
                    if (! is_numeric($val) || (int) $val < 0 || (int) $val > 100000) {
                        throw ValidationException::withMessages([
                            'progress_data.repetition_count' => 'Số biến/số lần trì niệm phải là số nguyên không âm (tối đa 100.000).',
                        ]);
                    }
                    $sanitized['repetition_count'] = (int) $val;
                }
                if (isset($progressData['target_repetitions'])) {
                    $val = $progressData['target_repetitions'];
                    if (is_numeric($val) && (int) $val > 0) {
                        $sanitized['target_repetitions'] = (int) $val;
                    }
                }
                break;

            case PracticeType::SutraReading:
            case PracticeType::SutraRecitation:
                if (isset($progressData['current_section_id'])) {
                    $sanitized['current_section_id'] = (int) $progressData['current_section_id'];
                }
                if (isset($progressData['completed_sections_count'])) {
                    $val = $progressData['completed_sections_count'];
                    if (! is_numeric($val) || (int) $val < 0 || (int) $val > 10000) {
                        throw ValidationException::withMessages([
                            'progress_data.completed_sections_count' => 'Số phẩm/đoạn đã đọc phải là số nguyên không âm.',
                        ]);
                    }
                    $sanitized['completed_sections_count'] = (int) $val;
                }
                break;

            case PracticeType::BellWoodenFish:
                if (isset($progressData['bell_count'])) {
                    $val = $progressData['bell_count'];
                    if (! is_numeric($val) || (int) $val < 0 || (int) $val > 100000) {
                        throw ValidationException::withMessages([
                            'progress_data.bell_count' => 'Số tiếng chuông phải là số nguyên không âm.',
                        ]);
                    }
                    $sanitized['bell_count'] = (int) $val;
                }
                if (isset($progressData['wooden_fish_count'])) {
                    $val = $progressData['wooden_fish_count'];
                    if (! is_numeric($val) || (int) $val < 0 || (int) $val > 100000) {
                        throw ValidationException::withMessages([
                            'progress_data.wooden_fish_count' => 'Số tiếng mõ phải là số nguyên không âm.',
                        ]);
                    }
                    $sanitized['wooden_fish_count'] = (int) $val;
                }
                break;
        }

        return $sanitized;
    }
}
