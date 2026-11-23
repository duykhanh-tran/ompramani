export type UserRole = "admin" | "user";

export interface UserProfile {
  dharma_name?: string | null;
  date_of_birth?: string | null;
  gender?: "male" | "female" | "other" | "prefer_not_to_say" | string | null;
  avatar?: string | null;
  avatar_url?: string | null;
  preferred_tradition?: {
    id: number;
    name: string;
    slug: string;
  } | null;
  preferred_tradition_id?: number | null;
  location?: string | null;
  bio?: string | null;
  dharma_journey_started_at?: string | null;
}

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  email_verified: boolean;
  profile?: UserProfile | null;
  created_at: string;
}

export interface LoginPayload {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface ForgotPasswordPayload {
  email: string;
}

export interface ResetPasswordPayload {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateProfilePayload {
  name?: string;
  dharma_name?: string | null;
  date_of_birth?: string | null;
  gender?: string | null;
  preferred_tradition_id?: number | null;
  location?: string | null;
  bio?: string | null;
  dharma_journey_started_at?: string | null;
}

export interface UpdateEmailPayload {
  email: string;
  current_password: string;
}

export interface ChangePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  data?: {
    user?: AuthUser;
    token?: string;
    email?: string;
    email_verified?: boolean;
    avatar?: string | null;
    avatar_url?: string | null;
  };
  token?: string;
  user?: AuthUser;
  message?: string;
}

export interface ApiValidationErrorPayload {
  message?: string;
  errors?: Record<string, string[]>;
}
