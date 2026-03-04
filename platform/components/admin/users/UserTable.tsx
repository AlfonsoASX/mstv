import { prisma } from "@/lib/prisma"
import { deleteUser } from "@/lib/actions/users"
import { Trash2 } from "lucide-react"

export async function UserTable() {
    const users = await prisma.user.findMany({
        orderBy: { createdAt: 'desc' }
    })

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th className="px-6 py-3">Name</th>
                        <th className="px-6 py-3">Username</th>
                        <th className="px-6 py-3">Role</th>
                        <th className="px-6 py-3">Created At</th>
                        <th className="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {users.map((user) => (
                        <tr key={user.id} className="bg-white border-b border-slate-100 hover:bg-slate-50">
                            <td className="px-6 py-4 font-medium text-slate-900">{user.name}</td>
                            <td className="px-6 py-4">{user.username}</td>
                            <td className="px-6 py-4">
                                <span className={`px-2 py-1 rounded-full text-xs font-semibold
                  ${user.role === 'ADMIN' ? 'bg-purple-100 text-purple-700' :
                                        user.role === 'GUARD' ? 'bg-blue-100 text-blue-700' :
                                            'bg-slate-100 text-slate-700'}`}>
                                    {user.role}
                                </span>
                            </td>
                            <td className="px-6 py-4 text-slate-500">
                                {new Date(user.createdAt).toLocaleDateString()}
                            </td>
                            <td className="px-6 py-4">
                                <form action={deleteUser.bind(null, user.id)}>
                                    <button className="text-red-500 hover:text-red-700 transition-colors">
                                        <Trash2 size={18} />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}
