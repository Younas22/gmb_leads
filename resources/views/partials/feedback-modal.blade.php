   <!-- Modal Body -->
            <form id="feedbackForm" class="p-4 sm:p-6">
                @csrf

                <!-- Rating Section -->
                <div class="mb-4 sm:mb-6">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 sm:mb-3">How would you rate our tool?</label>
                    <div class="flex justify-center space-x-1 sm:space-x-2 mb-2">
                        <button type="button" class="star-rating" data-rating="1">
                            <i class="fas fa-star text-xl sm:text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="2">
                            <i class="fas fa-star text-xl sm:text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="3">
                            <i class="fas fa-star text-xl sm:text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="4">
                            <i class="fas fa-star text-xl sm:text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="5">
                            <i class="fas fa-star text-xl sm:text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                    </div>
                    <p id="ratingText" class="text-center text-xs sm:text-sm text-gray-500">Click to rate</p>
                    <input type="hidden" name="rating" id="ratingValue" value="">
                </div>

                <!-- Feedback Type -->
                <div class="mb-4 sm:mb-6">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 sm:mb-3">What type of feedback?</label>
                    <div class="grid grid-cols-2 gap-2 sm:gap-3">
                        <label class="flex items-center p-2 sm:p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="suggestion" class="mr-2 sm:mr-3 text-blue-600 flex-shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center">
                                    <i class="fas fa-lightbulb text-yellow-500 mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                                    <span class="font-medium text-gray-800 text-xs sm:text-sm truncate">Suggestion</span>
                                </div>
                                <p class="text-[10px] sm:text-xs text-gray-500 truncate">Ideas for improvement</p>
                            </div>
                        </label>

                        <label class="flex items-center p-2 sm:p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="bug" class="mr-2 sm:mr-3 text-blue-600 flex-shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center">
                                    <i class="fas fa-bug text-red-500 mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                                    <span class="font-medium text-gray-800 text-xs sm:text-sm truncate">Bug Report</span>
                                </div>
                                <p class="text-[10px] sm:text-xs text-gray-500 truncate">Something not working</p>
                            </div>
                        </label>

                        <label class="flex items-center p-2 sm:p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="feature" class="mr-2 sm:mr-3 text-blue-600 flex-shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center">
                                    <i class="fas fa-plus-circle text-green-500 mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                                    <span class="font-medium text-gray-800 text-xs sm:text-sm truncate">Feature</span>
                                </div>
                                <p class="text-[10px] sm:text-xs text-gray-500 truncate">New functionality</p>
                            </div>
                        </label>

                        <label class="flex items-center p-2 sm:p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="general" class="mr-2 sm:mr-3 text-blue-600 flex-shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center">
                                    <i class="fas fa-comment text-blue-500 mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                                    <span class="font-medium text-gray-800 text-xs sm:text-sm truncate">General</span>
                                </div>
                                <p class="text-[10px] sm:text-xs text-gray-500 truncate">Other feedback</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Message -->
                <div class="mb-4 sm:mb-6">
                    <label for="feedbackMessage" class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Your Feedback</label>
                    <textarea
                        name="message"
                        id="feedbackMessage"
                        rows="3"
                        placeholder="Tell us what you think about our lead generation tool. What works well? What could be improved?"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm resize-none"
                        required></textarea>
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Minimum 10 characters</p>
                </div>

                <!-- Contact Permission -->
                <div class="mb-4 sm:mb-6">
                    <label class="flex items-start">
                        <input type="checkbox" name="contact_permission" class="mt-0.5 sm:mt-1 mr-2 sm:mr-3 text-blue-600 flex-shrink-0">
                        <div class="min-w-0">
                            <span class="text-xs sm:text-sm text-gray-700">Allow us to contact you about this feedback</span>
                            <p class="text-[10px] sm:text-xs text-gray-500">We may reach out for clarification or updates</p>
                        </div>
                    </label>
                </div>

                <!-- Buttons -->
                <div class="flex space-x-2 sm:space-x-3">
                    <button
                        type="button"
                        onclick="closeFeedbackModal()"
                        class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-xs sm:text-sm">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        id="submitFeedback"
                        class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed text-xs sm:text-sm">
                        <i class="fas fa-paper-plane mr-1 sm:mr-2"></i>
                        Submit Feedback
                    </button>
                </div>
            </form>
