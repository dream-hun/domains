import { Head, router } from '@inertiajs/react';
import { Upload, X } from 'lucide-react';
import React, { useCallback, useRef, useState } from 'react';
import * as tus from 'tus-js-client';
import GameController, { index } from '@/actions/App/Http/Controllers/Admin/GameController';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Game = {
    id: number;
    uuid: string;
    title: string;
    vimeo_status: string | null;
};

export default function UploadGame({ game }: { game: Game }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Games', href: index().url },
        { title: game.title, href: '#' },
        { title: 'Upload Video', href: GameController.showUpload(game.uuid).url },
    ];

    const [file, setFile] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [progress, setProgress] = useState(0);
    const [status, setStatus] = useState<'idle' | 'uploading' | 'paused' | 'done' | 'error'>(
        'idle',
    );
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const uploadRef = useRef<tus.Upload | null>(null);
    const inputRef = useRef<HTMLInputElement | null>(null);

    function selectFile(selected: File | null) {
        setFile(selected);
        setProgress(0);
        setStatus('idle');
        setErrorMessage(null);
        uploadRef.current = null;
    }

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    }, []);

    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        const dropped = e.dataTransfer.files[0];
        if (dropped?.type.startsWith('video/')) {
            selectFile(dropped);
        }
    }, []);

    async function startUpload() {
        if (!file) return;

        setStatus('uploading');
        setErrorMessage(null);

        const xsrfToken = decodeURIComponent(
            document.cookie
                .split('; ')
                .find((c) => c.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        );

        let uploadLink: string;
        try {
            const response = await fetch(GameController.initiateUpload(game.uuid).url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ file_size: file.size, file_name: file.name }),
            });

            if (!response.ok) throw new Error('Failed to create upload session.');

            const data = await response.json();
            uploadLink = data.upload_link;
        } catch (err) {
            setStatus('error');
            setErrorMessage(err instanceof Error ? err.message : 'Unknown error occurred.');
            return;
        }

        const upload = new tus.Upload(file, {
            uploadUrl: uploadLink,
            chunkSize: 5 * 1024 * 1024,
            retryDelays: [0, 3000, 5000, 10000, 20000],
            onProgress(bytesUploaded, bytesTotal) {
                setProgress(Math.round((bytesUploaded / bytesTotal) * 100));
            },
            onSuccess() {
                setProgress(100);
                setStatus('done');
                router.patch(GameController.completeUpload(game.uuid).url);
            },
            onError(error) {
                setStatus('error');
                setErrorMessage(error.message ?? 'Upload failed.');
            },
        });

        uploadRef.current = upload;
        upload.start();
    }

    function pauseUpload() {
        uploadRef.current?.abort();
        setStatus('paused');
    }

    function resumeUpload() {
        setStatus('uploading');
        uploadRef.current?.start();
    }

    const isUploading = status === 'uploading';
    const isPaused = status === 'paused';
    const isActive = isUploading || isPaused || status === 'done';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Upload Video — ${game.title}`} />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Upload Video</h1>
                    <p className="text-muted-foreground text-sm">
                        Upload a video for <span className="font-medium">{game.title}</span>.
                        {game.vimeo_status === 'complete' && (
                            <span className="ml-2 font-medium text-green-600">
                                A video has already been uploaded.
                            </span>
                        )}
                    </p>
                </div>

                <div className="max-w-lg space-y-4">
                    {/* Drop Zone */}
                    <input
                        ref={inputRef}
                        type="file"
                        accept="video/*"
                        className="hidden"
                        onChange={(e) => selectFile(e.target.files?.[0] ?? null)}
                        disabled={isUploading}
                    />

                    {!file ? (
                        <button
                            type="button"
                            onClick={() => inputRef.current?.click()}
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onDrop={handleDrop}
                            disabled={isUploading}
                            className={`flex w-full cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed px-6 py-12 text-center transition-colors ${
                                isDragging
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border hover:border-primary/50 hover:bg-muted/50'
                            }`}
                        >
                            <Upload className="text-muted-foreground size-10" />
                            <div>
                                <p className="text-sm font-medium">
                                    Drop a video file here, or{' '}
                                    <span className="text-primary">browse</span>
                                </p>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    MP4, MOV, AVI, MKV and other video formats
                                </p>
                            </div>
                        </button>
                    ) : (
                        <div className="bg-muted/50 flex items-center gap-3 rounded-lg border px-4 py-3">
                            <Upload className="text-muted-foreground size-5 shrink-0" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">{file.name}</p>
                                <p className="text-muted-foreground text-xs">
                                    {(file.size / 1024 / 1024).toFixed(2)} MB
                                </p>
                            </div>
                            {!isUploading && status !== 'done' && (
                                <button
                                    type="button"
                                    onClick={() => selectFile(null)}
                                    className="text-muted-foreground hover:text-foreground shrink-0"
                                >
                                    <X className="size-4" />
                                </button>
                            )}
                        </div>
                    )}

                    {/* Progress Bar */}
                    {isActive && (
                        <div className="space-y-1">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">
                                    {status === 'done'
                                        ? 'Complete'
                                        : isPaused
                                          ? 'Paused'
                                          : 'Uploading...'}
                                </span>
                                <span className="font-medium">{progress}%</span>
                            </div>
                            <div className="bg-secondary h-2 w-full overflow-hidden rounded-full">
                                <div
                                    className={`h-full rounded-full transition-all duration-300 ${status === 'done' ? 'bg-green-500' : 'bg-primary'}`}
                                    style={{ width: `${progress}%` }}
                                />
                            </div>
                        </div>
                    )}

                    {errorMessage && <p className="text-sm text-red-600">{errorMessage}</p>}

                    {status === 'done' && (
                        <p className="text-sm font-medium text-green-600">
                            Upload complete! Redirecting...
                        </p>
                    )}

                    {/* Actions */}
                    <div className="flex gap-2">
                        {!isUploading && !isPaused && status !== 'done' && (
                            <Button
                                onClick={startUpload}
                                disabled={!file}
                                variant="default"
                            >
                                Start Upload
                            </Button>
                        )}
                        {isUploading && (
                            <Button onClick={pauseUpload} variant="secondary">
                                Pause
                            </Button>
                        )}
                        {isPaused && (
                            <Button onClick={resumeUpload} variant="default">
                                Resume
                            </Button>
                        )}
                        <Button variant="secondary" onClick={() => router.visit(index().url)}>
                            Back to Games
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
