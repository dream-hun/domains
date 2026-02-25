import { motion, AnimatePresence } from 'framer-motion';
import { useState, useEffect } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

const heroSlides = [
    {
        headline: 'Every game tells\na story.',
        description:
            'Track your performance, challenge rivals, and climb the leaderboard. Your court, your legacy.',
    },
    {
        headline: 'Rise through\nthe ranks.',
        description:
            'Compete in challenges, earn badges, and prove you belong at the top. The leaderboard awaits.',
    },
    {
        headline: 'Your highlights,\nyour legacy.',
        description:
            'Upload game footage, review your plays, and share your best moments with the community.',
    },
    {
        headline: 'Built for\ncompetitors.',
        description:
            'From pickup games to tournaments — track every stat, every win, every step of your journey.',
    },
    {
        headline: 'The court\nnever lies.',
        description:
            'Submit your scores, verify results, and let your game speak for itself. No shortcuts.',
    },
];

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const [slideIndex, setSlideIndex] = useState(0);

    useEffect(() => {
        const id = setInterval(() => {
            setSlideIndex((i) => (i + 1) % heroSlides.length);
        }, 5000);
        return () => clearInterval(id);
    }, []);

    return (
        <div className="grid min-h-dvh lg:grid-cols-2">
            {/* Left: hero panel */}
            <div className="relative hidden lg:block">
                <img
                    src="https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=1400&q=80"
                    alt="Youth playing basketball on an outdoor court"
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div className="absolute inset-0 bg-linear-to-r from-black/60 via-black/40 to-black/70" />
                <div className="absolute inset-0 flex flex-col justify-end p-10 pb-12">
                    <div className="relative h-36 overflow-hidden">
                        <AnimatePresence initial={false} mode="wait">
                            <motion.div
                                key={slideIndex}
                                className="absolute inset-0"
                                initial={{ opacity: 0, y: 16 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, y: -12 }}
                                transition={{ duration: 0.4, ease: 'easeOut' }}
                            >
                                <h2 className="mb-3 font-heading text-3xl leading-tight font-bold whitespace-pre-line text-white xl:text-4xl">
                                    {heroSlides[slideIndex].headline}
                                </h2>
                                <p className="max-w-sm font-body text-sm leading-relaxed text-white/70">
                                    {heroSlides[slideIndex].description}
                                </p>
                            </motion.div>
                        </AnimatePresence>
                    </div>
                    <div className="mt-6 flex items-center gap-1.5">
                        {heroSlides.map((_, i) => (
                            <button
                                key={i}
                                type="button"
                                onClick={() => setSlideIndex(i)}
                                className={`rounded-full transition-all duration-300 ${i === slideIndex ? 'h-1 w-6 bg-chart-1' : 'h-1.5 w-1.5 bg-white/30 hover:bg-white/50'}`}
                                aria-label={`Go to slide ${i + 1}`}
                            />
                        ))}
                    </div>
                </div>
            </div>

            {/* Right: form panel */}
            <motion.div
                className="flex flex-col items-center justify-center bg-muted/30 px-8 py-10 sm:px-0 lg:px-10"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.4, ease: 'easeOut' }}
            >
                <div className="w-full max-w-md">
                    <motion.div
                        className="mb-6 flex justify-center"
                        initial={{ opacity: 0, y: -12 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                            duration: 0.4,
                            delay: 0.3,
                            ease: 'easeOut',
                        }}
                    >
                        <AppLogoIcon className="h-9 w-auto" />
                    </motion.div>
                    <motion.div
                        className="rounded-xl border border-border bg-card px-6 py-8 shadow-sm sm:px-8 sm:py-10"
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                            duration: 0.4,
                            delay: 0.5,
                            ease: 'easeOut',
                        }}
                    >
                        <div className="flex flex-col gap-8">
                            <div className="flex flex-col items-center gap-4">
                                <div className="space-y-2 text-center">
                                    <h1 className="text-xl font-medium text-card-foreground">
                                        {title}
                                    </h1>
                                    {description && (
                                        <p className="text-center text-sm text-muted-foreground">
                                            {description}
                                        </p>
                                    )}
                                </div>
                            </div>
                            {children}
                        </div>
                    </motion.div>
                </div>
            </motion.div>
        </div>
    );
}
