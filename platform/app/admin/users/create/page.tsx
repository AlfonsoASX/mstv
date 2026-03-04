import { CreateUserForm } from "@/components/admin/users/CreateUserForm"
import Link from "next/link"
import { ArrowLeft } from "lucide-react"

export default function CreateUserPage() {
    return (
        <div className="max-w-2xl mx-auto">
            <div className="mb-6">
                <Link href="/admin/users" className="text-sm text-muted hover:text-slate-900 flex items-center mb-2">
                    <ArrowLeft className="w-4 h-4 mr-1" />
                    Back to Users
                </Link>
                <h1 className="heading-2">Create New User</h1>
                <p className="text-muted">Add a new user to the system.</p>
            </div>

            <div className="card">
                <CreateUserForm />
            </div>
        </div>
    )
}
