"use server"

import { prisma } from "@/lib/prisma"
import { revalidatePath } from "next/cache"
import { redirect } from "next/navigation"
import bcrypt from "bcryptjs"
import { z } from "zod"

const UserSchema = z.object({
    name: z.string().min(2, "Name must be at least 2 characters"),
    username: z.string().min(3, "Username must be at least 3 characters"),
    password: z.string().min(6, "Password must be at least 6 characters").optional(),
    role: z.enum(["ADMIN", "SUPERVISOR", "GUARD", "HR", "CLIENT"]),
})

export async function createUser(prevState: any, formData: FormData) {
    const validatedFields = UserSchema.safeParse({
        name: formData.get("name"),
        username: formData.get("username"),
        password: formData.get("password"),
        role: formData.get("role"),
    })

    if (!validatedFields.success) {
        return {
            errors: validatedFields.error.flatten().fieldErrors,
            message: "Missing Fields. Failed to Create User.",
        }
    }

    const { name, username, password, role } = validatedFields.data

    if (!password) {
        return { message: "Password is required for new users." }
    }

    const hashedPassword = await bcrypt.hash(password, 10)

    try {
        await prisma.user.create({
            data: {
                name,
                username,
                password: hashedPassword,
                role,
            },
        })
    } catch (error) {
        return {
            message: "Database Error: Failed to Create User. Username might be taken.",
        }
    }

    revalidatePath("/admin/users")
    redirect("/admin/users")
}

export async function deleteUser(id: string) {
    try {
        await prisma.user.delete({
            where: { id },
        })
        revalidatePath("/admin/users")
        return { message: "User Deleted" }
    } catch (error) {
        return { message: "Database Error: Failed to Delete User." }
    }
}
