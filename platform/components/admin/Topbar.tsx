"use client"

import { useSession } from "next-auth/react"

export function Topbar() {
    const { data: session } = useSession()

    return (
        <header className="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10">
            <div className="text-sm font-medium text-muted">
                {/* Breadcrumbs or Page Title could go here */}
                Overview
            </div>

            <div className="flex items-center gap-4">
                <div className="text-right">
                    <div className="text-sm font-medium text-slate-900 dark:text-white">
                        {session?.user?.name || "Admin User"}
                    </div>
                    <div className="text-xs text-muted">
                        {session?.user?.role || "Administrator"}
                    </div>
                </div>
                <div className="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                    {session?.user?.name?.[0] || "A"}
                </div>
            </div>
        </header>
    )
}
