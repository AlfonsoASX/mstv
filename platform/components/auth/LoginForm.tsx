"use client"

import { useState } from "react"
import { signIn } from "next-auth/react"
import { useRouter } from "next/navigation"
import { Loader2 } from "lucide-react"

export function LoginForm() {
    const router = useRouter()
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState("")

    async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault()
        setLoading(true)
        setError("")

        const formData = new FormData(e.currentTarget)
        const username = formData.get("username") as string
        const password = formData.get("password") as string

        try {
            const res = await signIn("credentials", {
                username,
                password,
                redirect: false,
            })

            if (res?.error) {
                setError("Invalid credentials")
                setLoading(false)
            } else {
                router.push("/admin") // Default redirect, middleware will handle role-based routing later
                router.refresh()
            }
        } catch (err) {
            setError("Something went wrong")
            setLoading(false)
        }
    }

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            {error && (
                <div className="p-3 text-sm text-red-500 bg-red-50 border border-red-200 rounded-md">
                    {error}
                </div>
            )}
            <div>
                <label className="label" htmlFor="username">
                    Username
                </label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    required
                    className="input"
                    placeholder="Enter your username"
                    disabled={loading}
                />
            </div>
            <div>
                <label className="label" htmlFor="password">
                    Password
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    className="input"
                    placeholder="Enter your password"
                    disabled={loading}
                />
            </div>
            <button
                type="submit"
                disabled={loading}
                className="btn btn-primary btn-full mt-4"
            >
                {loading ? <Loader2 className="animate-spin mr-2 h-4 w-4" /> : null}
                Sign In
            </button>
        </form>
    )
}
