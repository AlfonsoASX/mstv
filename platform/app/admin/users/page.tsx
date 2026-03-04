import { prisma } from "@/lib/prisma"
import Link from "next/link"
import { Plus, Trash2 } from "lucide-react"
import { deleteUser } from "@/lib/actions/users"

export default async function UsersPage() {
    const users = await prisma.user.findMany({
        orderBy: { createdAt: "desc" },
    })

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="heading-2 mb-2">Users</h1>
                    <p className="text-muted">Manage system users and their roles.</p>
                </div>
                <Link href="/admin/users/create" className="btn btn-primary">
                    <Plus className="w-4 h-4 mr-2" />
                    Add User
                </Link>
            </div>

            <div className="card overflow-hidden p-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th className="px-6 py-4 font-semibold text-slate-900">Name</th>
                                <th className="px-6 py-4 font-semibold text-slate-900">Username</th>
                                <th className="px-6 py-4 font-semibold text-slate-900">Role</th>
                                <th className="px-6 py-4 font-semibold text-slate-900">Created At</th>
                                <th className="px-6 py-4 font-semibold text-slate-900 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {users.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-8 text-center text-muted">
                                        No users found.
                                    </td>
                                </tr>
                            ) : (
                                users.map((user) => (
                                    <tr key={user.id} className="hover:bg-slate-50/50">
                                        <td className="px-6 py-4 font-medium text-slate-900">{user.name}</td>
                                        <td className="px-6 py-4 text-slate-600">{user.username}</td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                ${user.role === 'ADMIN' ? 'bg-purple-100 text-purple-800' :
                                                    user.role === 'SUPERVISOR' ? 'bg-blue-100 text-blue-800' :
                                                        user.role === 'GUARD' ? 'bg-emerald-100 text-emerald-800' :
                                                            'bg-slate-100 text-slate-800'}`}>
                                                {user.role}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-slate-600">
                                            {new Date(user.createdAt).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <form action={async () => {
                                                "use server"
                                                await deleteUser(user.id)
                                            }}>
                                                <button className="text-slate-400 hover:text-red-600 transition-colors">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    )
}
