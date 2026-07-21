<template>
    <div class="main-container pt-8 pb-16">
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <h1 class="text-slate-900 text-2xl lg:text-3xl font-bold leading-tight">
                {{ $t("All Categories") }}
            </h1>
            <p v-if="!isLoading" class="text-slate-500 text-sm">
                {{ $t("Browse") }} {{ categories.length }} {{ $t("categories") }}
            </p>
        </div>

        <!-- Loading skeletons -->
        <div v-if="isLoading" class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-8">
            <SkeletonLoader
                v-for="i in 4"
                :key="i"
                class="w-full h-56 rounded-2xl"
            />
        </div>

        <!-- Empty -->
        <div
            v-else-if="categories.length == 0"
            class="flex flex-col items-center justify-center py-24 text-center"
        >
            <Squares2X2Icon class="w-12 h-12 text-slate-300" />
            <p class="mt-3 text-slate-950 text-xl font-medium leading-7">
                {{ $t("No Categories Found") }}
            </p>
        </div>

        <!-- Category directory -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-8">
            <div
                v-for="category in categories"
                :key="category.id"
                class="group bg-white rounded-2xl border border-slate-200 hover:border-primary/40 hover:shadow-lg hover:shadow-slate-200/60 transition-all duration-300 p-5 sm:p-6 flex flex-col"
            >
                <!-- Card header (whole row navigates to the category's products) -->
                <button
                    type="button"
                    class="flex items-center gap-4 text-left w-full"
                    @click="goToProducts(category.id)"
                >
                    <div
                        class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 shrink-0 flex items-center justify-center"
                    >
                        <img
                            v-if="category.thumbnail"
                            :src="category.thumbnail"
                            :alt="category.name"
                            class="w-full h-full object-cover"
                        />
                        <Squares2X2Icon v-else class="w-7 h-7 text-slate-400" />
                    </div>

                    <div class="min-w-0 grow">
                        <h2
                            class="text-slate-900 text-lg font-semibold leading-snug truncate group-hover:text-primary transition-colors duration-300"
                        >
                            {{ category.name }}
                        </h2>
                        <p class="text-slate-500 text-sm">
                            {{ category.total_products }} {{ $t("Items") }}
                        </p>
                    </div>

                    <span
                        class="w-9 h-9 rounded-full bg-slate-50 group-hover:bg-primary group-hover:text-white text-slate-400 flex items-center justify-center shrink-0 transition-all duration-300"
                    >
                        <ArrowRightIcon class="w-4 h-4" />
                    </span>
                </button>

                <!-- Subcategories -->
                <div
                    v-if="category.sub_categories && category.sub_categories.length"
                    class="mt-5"
                >
                    <p
                        class="text-slate-400 text-[11px] font-semibold uppercase tracking-wider mb-2.5"
                    >
                        {{ $t("Subcategories") }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="sub in category.sub_categories"
                            :key="sub.id"
                            type="button"
                            class="px-3 py-1.5 rounded-full border border-slate-200 text-slate-600 text-sm hover:border-primary hover:text-primary hover:bg-primary-50 transition-all duration-300"
                            @click="goToProducts(category.id, { subcategory: sub.id })"
                        >
                            {{ sub.name }}
                        </button>
                    </div>
                </div>

                <!-- Category attributes -->
                <div
                    v-if="category.attributes && category.attributes.length"
                    class="mt-5 pt-5 border-t border-slate-100 flex flex-col gap-3"
                >
                    <div
                        v-for="attribute in category.attributes"
                        :key="attribute.id"
                        class="flex flex-col gap-2"
                    >
                        <button
                            type="button"
                            class="text-slate-700 text-sm font-semibold text-left hover:text-primary transition-colors duration-300 w-fit"
                            @click="goToProducts(category.id, { attribute: attribute.id })"
                        >
                            {{ attribute.name }}
                        </button>
                        <div
                            v-if="attribute.sub_attributes && attribute.sub_attributes.length"
                            class="flex flex-wrap gap-1.5"
                        >
                            <button
                                v-for="sub in attribute.sub_attributes"
                                :key="sub.id"
                                type="button"
                                class="px-2.5 py-1 rounded-md bg-slate-50 text-slate-500 text-xs hover:bg-primary-50 hover:text-primary transition-all duration-300"
                                @click="goToProducts(category.id, { attribute: sub.id })"
                            >
                                {{ sub.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer link -->
                <button
                    type="button"
                    class="mt-5 inline-flex items-center gap-1.5 text-primary text-sm font-semibold hover:gap-2.5 transition-all duration-300 w-fit"
                    @click="goToProducts(category.id)"
                >
                    {{ $t("View all products") }}
                    <ArrowRightIcon class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ArrowRightIcon, Squares2X2Icon } from "@heroicons/vue/24/outline";
import SkeletonLoader from "../components/SkeletonLoader.vue";
import { useMaster } from "../stores/MasterStore";

const master = useMaster();
const router = useRouter();

const categories = ref([]);
const isLoading = ref(true);

onMounted(() => {
    fetchCategories();
    window.scrollTo(0, 0);
});

const fetchCategories = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("/categories");
        const list = response.data.data.categories;

        // load each category's attribute tree in parallel and attach it
        const attributeResponses = await Promise.all(
            list.map((category) =>
                axios
                    .get(`/category/${category.id}/attributes`, {
                        headers: { "Accept-Language": master.locale || "en" },
                    })
                    .then((res) => res.data.data.attributes)
                    .catch(() => [])
            )
        );

        list.forEach((category, index) => {
            category.attributes = attributeResponses[index] || [];
        });

        categories.value = list;
    } finally {
        isLoading.value = false;
    }
};

// Hand the clicked ids to the Products page (URL stays /products) so the
// matching option arrives preselected. Mirrors the navbar handoff.
const goToProducts = (categoryId, options = {}) => {
    master.pendingCategory = {
        category: categoryId,
        subcategory: options.subcategory || null,
        attribute: options.attribute || null,
    };
    router.push("/products");
};
</script>
