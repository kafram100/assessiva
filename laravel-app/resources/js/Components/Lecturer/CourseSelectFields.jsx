import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';

export const CUSTOM_COURSE_VALUE = '__custom__';

export function courseSelectValue(data, linkableCourses) {
    const list = linkableCourses ?? [];
    if (list.length === 0) {
        return null;
    }
    const rawId = data.course_id;
    const id =
        rawId === '' || rawId === undefined || rawId === null
            ? null
            : Number(rawId);
    if (id != null && !Number.isNaN(id) && list.some((c) => c.id === id)) {
        return String(id);
    }
    const name = String(data.course_name ?? '').trim();
    const code = String(data.course_code ?? '').trim();
    if (name !== '' || code !== '') {
        return CUSTOM_COURSE_VALUE;
    }

    return '';
}

const defaultSelectClass =
    'mt-1 block w-full rounded-md border-theme-line bg-theme-glass text-theme-strong shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

/**
 * Course picker: select from {@link linkableCourses} (scoped server-side by role)
 * or optional manual name/code when "Other" is chosen / when the list is empty.
 */
export default function CourseSelectFields({
    data,
    setData,
    errors = {},
    linkableCourses = [],
    selectClassName = defaultSelectClass,
    hintWhenList = null,
    hintWhenEmpty = null,
}) {
    const courses = linkableCourses ?? [];
    const hasCourses = courses.length > 0;
    const sel = courseSelectValue(data, courses);
    const showCustomCourseFields =
        hasCourses && sel === CUSTOM_COURSE_VALUE;

    const defaultHintList =
        hintWhenList ??
        'Pick a course you are allowed to access (same rules as Courses & rosters). The label is stored on the exam and can link the roster.';

    const defaultHintEmpty =
        hintWhenEmpty ??
        'You have no courses visible under your role. Add a course under Courses & rosters (or ask an administrator) to pick from a list next time.';

    return (
        <>
            {hasCourses ? (
                <div>
                    <InputLabel htmlFor="course_pick" value="Course" />
                    <select
                        id="course_pick"
                        className={selectClassName}
                        required
                        value={sel}
                        onChange={(e) => {
                            const v = e.target.value;
                            if (v === CUSTOM_COURSE_VALUE) {
                                setData('course_id', '');
                                return;
                            }
                            const id = Number(v);
                            const c = courses.find((row) => row.id === id);
                            if (!c) {
                                return;
                            }
                            setData('course_id', id);
                            setData('course_name', c.name ?? '');
                            setData(
                                'course_code',
                                c.code != null && c.code !== ''
                                    ? String(c.code)
                                    : '',
                            );
                        }}
                    >
                        <option value="" disabled>
                            Select a course…
                        </option>
                        {courses.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.code ? `${c.code} - ` : ''}
                                {c.name}
                            </option>
                        ))}
                        <option value={CUSTOM_COURSE_VALUE}>
                            Other (enter course name manually)
                        </option>
                    </select>
                    <p className="mt-1 text-xs text-theme-dim">{defaultHintList}</p>
                    <InputError message={errors.course_id} className="mt-2" />
                    {showCustomCourseFields ? (
                        <div className="mt-4 space-y-4 rounded-lg border border-theme-line/60 bg-theme-glass p-4">
                            <div>
                                <InputLabel
                                    htmlFor="course_name"
                                    value="Course name"
                                />
                                <TextInput
                                    id="course_name"
                                    className="mt-1 block w-full"
                                    value={data.course_name}
                                    onChange={(e) =>
                                        setData('course_name', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.course_name}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="course_code"
                                    value="Course code (optional)"
                                />
                                <TextInput
                                    id="course_code"
                                    className="mt-1 block w-full"
                                    value={data.course_code ?? ''}
                                    onChange={(e) =>
                                        setData('course_code', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.course_code}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    ) : null}
                </div>
            ) : (
                <>
                    <div>
                        <InputLabel htmlFor="course_name" value="Course name" />
                        <TextInput
                            id="course_name"
                            className="mt-1 block w-full"
                            value={data.course_name}
                            onChange={(e) =>
                                setData('course_name', e.target.value)
                            }
                            required
                        />
                        <InputError
                            message={errors.course_name}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="course_code"
                            value="Course code (optional)"
                        />
                        <TextInput
                            id="course_code"
                            className="mt-1 block w-full"
                            value={data.course_code ?? ''}
                            onChange={(e) =>
                                setData('course_code', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.course_code}
                            className="mt-2"
                        />
                    </div>
                    <p className="text-xs text-theme-dim">{defaultHintEmpty}</p>
                </>
            )}
        </>
    );
}
