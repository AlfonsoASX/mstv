"use client"

import { useFormState, useFormStatus } from "react-dom"
import { createUser } from "@/lib/actions/users"
import { Loader2 } from "lucide-react"

const initialState = {
    message: "",
    errors: {},
}

function SubmitButton() {
    const { pending } = useFormStatus()

    return (
        <button
            type="submit"
            disabled={pending}
            className="btn btn-primary w-full"
        >
            {pending ? <Loader2 className="animate-spin mr-2 h-4 w-4" /> : null}
            Create User
        </button>
    )
}

export function CreateUserForm() {
    const [state, dispatch] = useFormState(createUser, initialState)

    return (
        <form action={dispatch} className="space-y-4">
            <div>
                <label className="label" htmlFor="name">Name</label>
                <input id="name" name="name" type="text" className="input" required />
                {state?.errors?.name && <p className="text-red-500 text-xs mt-1">{state.errors.name}</p>}
            </div>

            <div>
                <label className="label" htmlFor="username">Username</label>
                <input id="username" name="username" type="text" className="input" required />
                {state?.errors?.username && <p className="text-red-500 text-xs mt-1">{state.errors.username}</p>}
            </div>

            <div>
                <label className="label" htmlFor="password">Password</label>
                <input id="password" name="password" type="password" className="input" required />
                {state?.errors?.password && <p className="text-red-500 text-xs mt-1">{state.errors.password}</p>}
            </div>

            <div>
                <label className="label" htmlFor="role">Role</label>
                <select id="role" name="role" className="input" defaultValue="GUARD">
                    <option value="GUARD">Guard</option>
                    <option value="SUPERVISOR">Supervisor</option>
                    <option value="ADMIN">Admin</option>
                    <option value="HR">HR</option>
                    <option value="CLIENT">Client</option>
                </select>
            </div>

            {state?.message && (
                <p className={`text-sm ${state.message === "User Created" ? "text-green-600" : "text-red-600"}`}>
                    {state.message}
                </p>
            )}

            <SubmitButton />
        </form>
    )
}
