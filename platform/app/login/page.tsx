import { LoginForm } from "@/components/auth/LoginForm"

export default function LoginPage() {
    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 p-4">
            <div className="w-full max-w-md">
                <div className="text-center mb-8">
                    <h1 className="heading-1 text-slate-900 dark:text-white mb-2">
                        Security Guard
                    </h1>
                    <p className="text-muted">
                        Management System
                    </p>
                </div>

                <div className="card bg-white dark:bg-slate-800 shadow-xl border-slate-200 dark:border-slate-700">
                    <div className="mb-6">
                        <h2 className="heading-3 mb-1">Welcome back</h2>
                        <p className="text-sm text-muted">
                            Please sign in to your account
                        </p>
                    </div>

                    <LoginForm />
                </div>

                <div className="text-center mt-8 text-sm text-muted">
                    &copy; {new Date().getFullYear()} Security Co. All rights reserved.
                </div>
            </div>
        </div>
    )
}
