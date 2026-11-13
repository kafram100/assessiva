import React, { useState, useEffect } from 'react';

/**
 * Robust UserAvatar component that gracefully falls back to a clean initial-based
 * avatar when no picture exists or if an image URL fails to load.
 */
export default function UserAvatar({
    user,
    src,
    size = 'md',
    className = '',
    alt = '',
}) {
    const [hasError, setHasError] = useState(false);

    // Resolve URL from src override or user.profile_picture_url
    const imageUrl = src !== undefined ? src : user?.profile_picture_url;
    const displayName = user?.name || alt || 'User';
    const initial = (displayName.trim().charAt(0) || 'U').toUpperCase();

    // Reset error whenever the image URL changes (e.g. after upload or prop change)
    useEffect(() => {
        setHasError(false);
    }, [imageUrl]);

    const sizeMap = {
        xs: 'h-5 w-5 text-[10px]',
        sm: 'h-6 w-6 text-xs',
        md: 'h-8 w-8 sm:h-7 sm:w-7 text-xs',
        lg: 'h-9 w-9 text-sm',
        xl: 'h-24 w-24 text-3xl',
    };

    const sizeClass = sizeMap[size] || size;

    if (imageUrl && !hasError) {
        return (
            <img
                src={imageUrl}
                alt={displayName}
                onError={() => setHasError(true)}
                className={`shrink-0 aspect-square rounded-full object-cover shadow-sm ring-1 ring-black/5 dark:ring-white/10 ${sizeClass} ${className}`}
            />
        );
    }

    return (
        <div
            className={`shrink-0 aspect-square rounded-full flex items-center justify-center font-bold text-white shadow-sm bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-600 select-none ring-1 ring-black/5 dark:ring-white/10 ${sizeClass} ${className}`}
            title={displayName}
            aria-label={displayName}
        >
            {initial}
        </div>
    );
}
