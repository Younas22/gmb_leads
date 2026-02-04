   <!-- Modal Body -->
            <form id="feedbackForm" class="p-6">
                @csrf
                
                <!-- Rating Section -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">How would you rate our tool?</label>
                    <div class="flex justify-center space-x-2 mb-2">
                        <button type="button" class="star-rating" data-rating="1">
                            <i class="fas fa-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="2">
                            <i class="fas fa-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="3">
                            <i class="fas fa-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="4">
                            <i class="fas fa-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                        <button type="button" class="star-rating" data-rating="5">
                            <i class="fas fa-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors"></i>
                        </button>
                    </div>
                    <p id="ratingText" class="text-center text-sm text-gray-500">Click to rate</p>
                    <input type="hidden" name="rating" id="ratingValue" value="">
                </div>

                <!-- Feedback Type -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">What type of feedback?</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="suggestion" class="mr-3 text-blue-600">
                            <div>
                                <div class="flex items-center">
                                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                                    <span class="font-medium text-gray-800">Suggestion</span>
                                </div>
                                <p class="text-xs text-gray-500">Ideas for improvement</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="bug" class="mr-3 text-blue-600">
                            <div>
                                <div class="flex items-center">
                                    <i class="fas fa-bug text-red-500 mr-2"></i>
                                    <span class="font-medium text-gray-800">Bug Report</span>
                                </div>
                                <p class="text-xs text-gray-500">Something not working</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="feature" class="mr-3 text-blue-600">
                            <div>
                                <div class="flex items-center">
                                    <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                                    <span class="font-medium text-gray-800">Feature Request</span>
                                </div>
                                <p class="text-xs text-gray-500">New functionality</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="feedback_type" value="general" class="mr-3 text-blue-600">
                            <div>
                                <div class="flex items-center">
                                    <i class="fas fa-comment text-blue-500 mr-2"></i>
                                    <span class="font-medium text-gray-800">General</span>
                                </div>
                                <p class="text-xs text-gray-500">Other feedback</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Message -->
                <div class="mb-6">
                    <label for="feedbackMessage" class="block text-sm font-medium text-gray-700 mb-2">Your Feedback</label>
                    <textarea 
                        name="message" 
                        id="feedbackMessage" 
                        rows="4" 
                        placeholder="Tell us what you think about our lead generation tool. What works well? What could be improved?"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"
                        required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Minimum 10 characters</p>
                </div>

                <!-- Contact Permission -->
                <div class="mb-6">
                    <label class="flex items-start">
                        <input type="checkbox" name="contact_permission" class="mt-1 mr-3 text-blue-600">
                        <div>
                            <span class="text-sm text-gray-700">Allow us to contact you about this feedback</span>
                            <p class="text-xs text-gray-500">We may reach out for clarification or to update you on improvements</p>
                        </div>
                    </label>
                </div>

                <!-- Buttons -->
                <div class="flex space-x-3">
                    <button 
                        type="button" 
                        onclick="closeFeedbackModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        id="submitFeedback"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Feedback
                    </button>
                </div>
            </form>